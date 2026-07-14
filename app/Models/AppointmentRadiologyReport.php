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
     * @var array<int, string>
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
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