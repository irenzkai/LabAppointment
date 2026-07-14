<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'payment_status', // unpaid, paid
        'payment_receipt', // Stores path for uploaded proof of payment receipts
        
        // STATUS LOGIC & SOFT DELETION
        'status',
        'return_reason',
        'deleted_by_patient', // boolean
        'tested_at',
        'result_estimated_at',
        'results_released_at'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'appointment_date' => 'date',
        'patient_birthdate' => 'date',
        'deleted_by_patient' => 'boolean',
        'tested_at' => 'datetime',
        'result_estimated_at' => 'datetime', 
        'results_released_at' => 'datetime',
    ];

    /** 
     * --- RELATIONSHIPS --- 
     */

    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    public function dependent() 
    {
        return $this->belongsTo(Dependent::class);
    }

    public function services() 
    {
        return $this->belongsToMany(Service::class, 'appointment_service');
    }

    /**
     * Connect to the clinical findings and uploaded scans.
     */
    public function result() 
    {
        return $this->hasOne(AppointmentResult::class);
    }

    /** 
     * --- ACCESSORS, VALIDATORS, & HELPERS --- 
     */

    /**
     * Calculate the total price of all services in this appointment.
     */
    public function totalPrice() 
    {
        return $this->services->sum('price');
    }

    /**
     * Determine if an appointment is dynamically expired (24-hour unprogressed rule)
     */
    public function isExpired(): bool
    {
        // If it progressed to sampling, it can never expire
        if (in_array($this->status, ['tested', 'encoded', 'released'])) {
            return false;
        }

        $scheduledAt = Carbon::parse($this->appointment_date->format('Y-m-d') . ' ' . $this->time_slot);
        return Carbon::now()->greaterThan($scheduledAt->addHours(24));
    }

    /**
     * Checks if the patient is allowed to soft-delete this record from their dashboard
     */
    public function canBeDeletedByPatient(): bool
    {
        // PAID records are financially locked; they can NEVER be deleted or purged
        if ($this->payment_status === 'paid') {
            return false;
        }

        // Patients can only delete expired entries
        return $this->isExpired();
    }

    /**
     * Get the patient name, prioritizing the snapshot data.
     */
    public function getPatientNameAttribute() 
    {
        if (!empty($this->attributes['patient_name'])) return $this->attributes['patient_name'];
        if ($this->dependent_id && $this->dependent) return $this->dependent->name;
        return $this->user ? $this->user->name : 'UNKNOWN PATIENT';
    }

    /**
     * Get the patient sex, prioritizing the snapshot data.
     */
    public function getPatientSexAttribute() 
    {
        if (!empty($this->attributes['patient_sex'])) return $this->attributes['patient_sex'];
        if ($this->dependent_id && $this->dependent) return $this->dependent->sex;
        return $this->user ? $this->user->sex : 'N/A';
    }

    /**
     * Calculate age based on the prioritized birthdate source.
     */
    public function getPatientAgeAttribute() 
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
    public function getPatientAddressAttribute()
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