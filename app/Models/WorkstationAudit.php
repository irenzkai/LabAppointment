<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkstationAudit extends Model
{
    protected $fillable = [
        'appointment_result_id',
        'workstation_type',
        'v1_by',
        'v1_by_name',
        'v1_at',
        'v2_by',
        'v2_by_name',
        'v2_at'
    ];

    protected $casts = [
        'v1_at' => 'datetime',
        'v2_at' => 'datetime'
    ];

    public function result()
    {
        return $this->belongsTo(AppointmentResult::class, 'appointment_result_id');
    }

    public function encoder()
    {
        return $this->belongsTo(User::class, 'v1_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'v2_by');
    }
}