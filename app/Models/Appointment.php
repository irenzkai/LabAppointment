<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'dependent_id',
        'patient_name',      
        'patient_email',     
        'patient_phone',     
        'patient_sex',       
        'patient_birthdate', 
        'organization_name', 
        'batch_id',          
        'appointment_date',
        'time_slot',
        'patient_address',
        'status',
        'return_reason'
    ];

    protected $casts = [
        'appointment_date' => 'date',   
        'patient_birthdate' => 'date',   
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function services() {
        return $this->belongsToMany(Service::class, 'appointment_service');
    }

    // Helper to calculate total of all tests in this appointment
    public function totalPrice() {
        return $this->services->sum('price');
    }

    public function dependent() {
        return $this->belongsTo(Dependent::class);
    }

    // Helper to get patient name
     public function getPatientNameAttribute()
    {
        // 1. If this is a Bulk Appointment, use the name stored in the column
        // We use $this->attributes['patient_name'] to get the raw DB value
        if (!empty($this->attributes['patient_name'])) {
            return $this->attributes['patient_name'];
        }

        // 2. If it's a Dependent booking, use the family member's name
        if ($this->dependent_id && $this->dependent) {
            return $this->dependent->name;
        }

        // 3. Otherwise, it's a 'Self' booking, use the account holder's name
        return $this->user ? $this->user->name : 'Unknown Patient';
    }

    public function getPatientSexAttribute() {
        if ($this->batch_id) return $this->attributes['patient_sex'];
        if ($this->dependent_id) return $this->dependent->sex;
        return $this->user->sex;
    }

    public function getPatientAgeAttribute() {
        $date = null;
        if ($this->batch_id) $date = $this->patient_birthdate;
        elseif ($this->dependent_id) $date = $this->dependent->birthdate;
        else $date = $this->user->birthdate;

        return $date ? \Carbon\Carbon::parse($date)->age : 'N/A';
    }
}