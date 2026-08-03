<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentDrugTest extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appointment_drug_tests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_result_id',
        'cert_no',
        'status',
        'scan_path',
        'return_reason',
    ];

    /**
     * Relationship: Get the parent result record.
     */
    public function parentResult()
    {
        return $this->belongsTo(AppointmentResult::class, 'appointment_result_id');
    }
}