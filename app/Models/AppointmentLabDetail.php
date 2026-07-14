<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentLabDetail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_lab_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_result_id',
        'case_no',
        'released_by_name',
        'released_by_license',
        'validated_by_name',
        'validated_by_license',
        'validated_by_name_2',
        'validated_by_license_2',
    ];

    /**
     * Relationship: Get the parent result record.
     */
    public function result()
    {
        return $this->belongsTo(AppointmentResult::class, 'appointment_result_id');
    }
}