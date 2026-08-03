<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentRadiologyReport extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_radiology_reports';

    /**
     * The attributes that are mass assignable.
     * 
     * FIXED: Added 'xray_image' to fillable to enable mass-assignment writes on this sub-table.
     */
    protected $fillable = [
        'appointment_result_id',
        'case_no',
        'date_of_exam',
        'technique',
        'findings',
        'impression',
        'radiologist_name',
        'radiologist_license',
        'xray_image'
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'date_of_exam' => 'date',
    ];

    /**
     * Relationship: Get the parent result record.
     */
    public function result()
    {
        return $this->belongsTo(AppointmentResult::class, 'appointment_result_id');
    }
}