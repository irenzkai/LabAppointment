<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class Appointment extends Model
{
    /**
     * The attributes that are mass assignable.
     * Includes snapshot fields to "freeze" patient info at the time of booking.
     */
    protected $fillable = [
        'user_id',
        'dependent_id',
        'organization_name',
        'batch_id',
        'appointment_date',
        'time_slot',

        // Normalized Patient Name snapshots (1NF)
        'patient_first_name',
        'patient_middle_name',
        'patient_last_name',
        'patient_suffix',
        'patient_name', // Compiled string representation
        'patient_email',
        'patient_phone',
        'patient_sex',
        'patient_birthdate',

        // Referral Attachments (Optional)
        'referral_note',

        // Normalized Patient Address snapshots (3NF)
        'patient_street',
        'patient_barangay',
        'patient_city',
        'patient_province',

        // Settlement Methods & Audit Records
        'payment_method', // Cash, Cashless
        'payment_status', // unpaid, paid, invalid, refunded
        'payment_receipt', // Stores path for uploaded proof of payment receipts
        'payment_amount',  // Persists confirmed payment amount collected on-site

        // STATUS LOGIC, REVISION WORKFLOW & SOFT DELETION
        'status',
        'suggested_changes', // JSON payload holding staff proposed schedule/test adjustments
        'return_reason',
        'cancellation_reason',
        'deleted_by_patient', // boolean
        'tested_at',
        'result_estimated_at',
        'results_released_at',
    ];

    /**
     * The attributes that should be cast.
     * 'date:Y-m-d' enforces local date serialization across all JSON endpoints.
     */
    protected $casts = [
        'appointment_date' => 'date:Y-m-d',
        'patient_birthdate' => 'date:Y-m-d',
        'suggested_changes' => 'array',
        'deleted_by_patient' => 'boolean',
        'tested_at' => 'datetime',
        'result_estimated_at' => 'datetime',
        'results_released_at' => 'datetime',
        'payment_amount' => 'decimal:2',
    ];

    /**
     * Appends dynamic accessors so they are always serialized to JSON for Mobile and Web APIs.
     */
    protected $appends = [
        'patient_email',
        'patient_age',
        'patient_address',
    ];

    /** 
     * --- RELATIONSHIPS --- 
     */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dependent(): BelongsTo
    {
        return $this->belongsTo(Dependent::class);
    }

    /**
     * Individual atomic diagnostic services attached to this appointment.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'appointment_service');
    }

    /**
     * Diagnostic packages/bundles attached to this appointment.
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'appointment_package');
    }

    /**
     * Connect to the clinical findings and uploaded scans.
     */
    public function result(): HasOne
    {
        return $this->hasOne(AppointmentResult::class);
    }

    /** 
     * --- ACCESSORS, VALIDATORS, & HELPERS --- 
     */

    /**
     * Calculate the combined total bill of all individual services and packages booked.
     */
    public function totalPrice(): float
    {
        $servicesTotal = (float) $this->services->sum('price');
        $packagesTotal = (float) $this->packages->sum('price');

        return $servicesTotal + $packagesTotal;
    }

    /**
     * Returns a deduplicated collection of all individual Service models that must be tested,
     * merging standalone selected services with the services included inside booked packages.
     */
    public function allServices(): Collection
    {
        $directServices = $this->services;
        $packageServices = $this->packages->flatMap(function ($pkg) {
            return $pkg->services;
        });

        return $directServices->concat($packageServices)->unique('id')->values();
    }

    /**
     * Check if appointment has pending staff change suggestions awaiting patient confirmation.
     */
    public function hasSuggestedChanges(): bool
    {
        return $this->status === 'needs_review' && !empty($this->suggested_changes);
    }

    /**
     * Applies staff proposed changes upon patient agreement and moves appointment to approved.
     */
    public function acceptSuggestedChanges(): bool
    {
        if (!$this->hasSuggestedChanges()) {
            return false;
        }

        $changes = $this->suggested_changes;
        $updates = [
            'status' => 'approved',
            'suggested_changes' => null,
            'return_reason' => null,
        ];

        if (!empty($changes['appointment_date'])) {
            $updates['appointment_date'] = $changes['appointment_date'];
        }
        if (!empty($changes['time_slot'])) {
            $updates['time_slot'] = $changes['time_slot'];
        }

        $this->update($updates);

        if (isset($changes['service_ids']) && is_array($changes['service_ids'])) {
            $this->services()->sync($changes['service_ids']);
        }

        if (isset($changes['package_ids']) && is_array($changes['package_ids'])) {
            $this->packages()->sync($changes['package_ids']);
        }

        return true;
    }

    /**
     * Determine if an appointment is dynamically expired/voided (24-hour unprogressed rule).
     * Once an appointment has progressed to sampling ('tested', 'retest', 'encoded', 'released'),
     * it cannot be voided.
     */
    public function isExpired(): bool 
    {
        if (in_array($this->status, ['retest', 'tested', 'encoded', 'released'])) {
            return false;
        }

        $dateFormatted = ($this->appointment_date instanceof \DateTimeInterface)
            ? $this->appointment_date->format('Y-m-d')
            : (string) $this->appointment_date;

        $scheduledAt = Carbon::parse($dateFormatted . ' ' . $this->time_slot);

        return Carbon::now()->greaterThan($scheduledAt->addHours(24));
    }

    /**
     * Semantic alias for views: Check if appointment has been voided due to no-show.
     */
    public function isVoided(): bool
    {
        return $this->isExpired();
    }

    /**
     * Checks if the patient is allowed to soft-delete this record from their dashboard.
     */
    public function canBeDeletedByPatient(): bool
    {
        // PAID records are financially locked; they can NEVER be deleted or purged
        if ($this->payment_status === 'paid') {
            return false;
        }

        // Patients can only delete expired/voided entries
        return $this->isExpired();
    }

    /**
     * Get the patient name, prioritizing the snapshot data.
     */
    public function getPatientNameAttribute(): string
    {
        if (!empty($this->attributes['patient_name'])) {
            return $this->attributes['patient_name'];
        }

        if ($this->dependent_id && $this->dependent) {
            return $this->dependent->name;
        }

        return $this->user ? $this->user->name : 'UNKNOWN PATIENT';
    }

    /**
     * Get the patient email, prioritizing the snapshot data, falling back to linked User account.
     */
    public function getPatientEmailAttribute(): ?string
    {
        if (!empty($this->attributes['patient_email'])) {
            return $this->attributes['patient_email'];
        }

        return $this->user ? $this->user->email : null;
    }

    /**
     * Get the patient sex, prioritizing the snapshot data.
     */
    public function getPatientSexAttribute(): string
    {
        if (!empty($this->attributes['patient_sex'])) {
            return $this->attributes['patient_sex'];
        }

        if ($this->dependent_id && $this->dependent) {
            return $this->dependent->sex;
        }

        return $this->user ? $this->user->sex : 'N/A';
    }

    /**
     * Calculate age based on the prioritized birthdate source.
     */
    public function getPatientAgeAttribute(): string|int
    {
        $date = null;

        if (!empty($this->patient_birthdate)) {
            $date = $this->patient_birthdate;
        } elseif ($this->dependent_id && $this->dependent) {
            $date = $this->dependent->birthdate;
        } elseif ($this->user) {
            $date = $this->user->birthdate;
        }

        return $date ? Carbon::parse($date)->age : 'N/A';
    }

    /**
     * Dynamic Address Accessor (Compiles atomic snapshot fields into a single address string)
     */
    public function getPatientAddressAttribute(): string
    {
        if (!empty($this->patient_street)) {
            return strtoupper("{$this->patient_street}, BRGY. {$this->patient_barangay}, {$this->patient_city}, {$this->patient_province}");
        }

        return 'N/A';
    }

    /**
     * Check if the appointment has all required clinical sign-offs (v1 and v2).
     */
    public function isFullyVerified(): bool
    {
        $res = $this->result;
        if (!$res) return false;

        $reports = $res->included_reports ?? [];
        if (empty($reports)) return false;

        if (in_array('lab', $reports)) {
            if (!$res->lab_v1_at || !$res->lab_v2_at) return false;
        }
        if (in_array('med_cert', $reports) && !$res->med_verified_at) return false;
        if (in_array('drug', $reports) && !$res->drug_verified_at) return false;
        if (in_array('radio', $reports) && !$res->radio_verified_at) return false;

        return true;
    }
}