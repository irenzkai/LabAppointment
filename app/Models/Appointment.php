<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'dependent_id',
        'organization_name',
        'batch_id',
        'appointment_date',
        'time_slot',
        'patient_name',      
        'patient_email',     
        'patient_phone',     
        'patient_sex',       
        'patient_birthdate', 
        'patient_address',
        'status',
        'return_reason',
        'tested_at',
        'result_estimated_at',
        'results_released_at'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'patient_birthdate' => 'date',
        'tested_at' => 'datetime',
        'result_estimated_at' => 'datetime', 
        'results_released_at' => 'datetime',
    ];

    /** --- RELATIONSHIPS --- **/

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function dependent() {
        return $this->belongsTo(Dependent::class);
    }

    public function services() {
        return $this->belongsToMany(Service::class, 'appointment_service');
    }

    // Connect to the encoded results
    public function result() {
        return $this->hasOne(AppointmentResult::class);
    }

    /** --- ACCESSORS & HELPERS --- **/

    public function totalPrice() {
        return $this->services->sum('price');
    }

    public function getPatientNameAttribute() {
        if (!empty($this->attributes['patient_name'])) return $this->attributes['patient_name'];
        if ($this->dependent_id && $this->dependent) return $this->dependent->name;
        return $this->user ? $this->user->name : 'Unknown Patient';
    }

    public function getPatientSexAttribute() {
        if (!empty($this->attributes['patient_sex'])) return $this->attributes['patient_sex'];
        if ($this->dependent_id && $this->dependent) return $this->dependent->sex;
        return $this->user ? $this->user->sex : 'N/A';
    }

    public function getPatientAgeAttribute() {
        $date = null;
        if (!empty($this->patient_birthdate)) $date = $this->patient_birthdate;
        elseif ($this->dependent_id && $this->dependent) $date = $this->dependent->birthdate;
        elseif ($this->user) $date = $this->user->birthdate;

        return $date ? \Carbon\Carbon::parse($date)->age : 'N/A';
    }

    public function isStaff() {
        return in_array($this->role, ['staff', 'admin']);
    }

    /**
     * Check if the appointment has all required clinical verifications.
     */
    public function isFullyVerified(): bool
    {
        $res = $this->result; // Access the relationship
        if (!$res) return false;
        
        $reports = $res->included_reports ?? [];
        if (empty($reports)) return false;

        // Laboratory: Must have TWO timestamps (v1 AND v2)
        if (in_array('lab', $reports)) {
            if (!$res->lab_v1_at || !$res->lab_v2_at) return false;
        }

        // Others: Must have ONE timestamp
        if (in_array('med_cert', $reports) && !$res->med_verified_at) return false;
        if (in_array('drug', $reports) && !$res->drug_verified_at) return false;
        if (in_array('radio', $reports) && !$res->radio_verified_at) return false;

        return true;
    }
}