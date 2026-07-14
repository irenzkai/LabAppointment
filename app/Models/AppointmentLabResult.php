<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentLabResult extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_lab_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_result_id',
        'parameter_name',
        'observed_value',
        'reference_range',
    ];

    /**
     * Relationship: Get the parent result record.
     */
    public function result()
    {
        return $this->belongsTo(AppointmentResult::class, 'appointment_result_id');
    }
}