<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'user_id', 'dependent_id',
        'patient_name', 'patient_email', 'patient_phone', 'patient_sex', 'patient_birthdate', 
        'organization_name', 'batch_id',          
        'appointment_date', 'time_slot', 'patient_address',
        'status', 'return_reason',
        // NEW: Workflow Timestamps
        'tested_at', 'result_estimated_at', 'results_released_at'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'patient_birthdate' => 'date',
        // Ensure these are treated as Carbon objects for diffForHumans()
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
}