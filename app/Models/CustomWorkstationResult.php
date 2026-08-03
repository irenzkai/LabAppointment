<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomWorkstationResult extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_workstation_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_result_id',
        'name', // FIXED: Added to fillable to prevent silent stripping on Eloquent::create()
        'status',
        'cert_no',
        'scan_path',
        'return_reason'
    ];

    /**
     * Relationship: Get the parent result record.
     */
    public function parentResult()
    {
        return $this->belongsTo(AppointmentResult::class, 'appointment_result_id');
    }
}