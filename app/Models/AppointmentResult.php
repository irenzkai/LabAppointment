<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentResult extends Model
{
    protected $fillable = [
        'appointment_id', 
        'included_reports', 
        'lab_data', 'med_cert_data', 'drug_test_data', 'radio_data',
        'lab_scan', 'med_cert_scan', 'drug_test_scan', 'radio_scan', 'xray_image'
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