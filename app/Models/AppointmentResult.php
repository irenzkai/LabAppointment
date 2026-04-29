<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentResult extends Model
{
    protected $fillable = [
        'appointment_id', 
        'included_reports',
        'lab_data', 
        'med_cert_data', 
        'drug_test_data', 
        'radio_data',
        'lab_scan',
        'med_cert_scan',
        'drug_test_scan',
        'radio_scan',
        'xray_image',
        'lab_v1_by', 'lab_v1_at', 'lab_v2_by', 'lab_v2_at',
        'med_verified_by', 'med_verified_at',
        'drug_verified_by', 'drug_verified_at',
        'radio_verified_by', 'radio_verified_at'
    ];

    protected $casts = [
        'included_reports' => 'array', 
        'lab_data' => 'array',
        'med_cert_data' => 'array',
        'drug_test_data' => 'array',
        'radio_data' => 'array',
    ];

    /**
     * Relationship back to the main appointment.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}