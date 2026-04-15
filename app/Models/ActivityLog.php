<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'appointment_id', 'patient_name', 'action', 'reason'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Static helper to record any event across the system
     */
    public static function record($action, $reason = 'N/A', $patientName = 'SYSTEM', $appointmentId = null)
    {
        return self::create([
            'user_id' => Auth::id() ?? 0, // 0 if guest (registration)
            'appointment_id' => $appointmentId,
            'patient_name' => $patientName,
            'action' => strtoupper($action),
            'reason' => $reason
        ]);
    }
}