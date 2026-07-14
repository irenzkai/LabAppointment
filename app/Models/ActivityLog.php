<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    /**
     * The attributes that are mass assignable.
     * 
     * user_id: The ID of the person performing the action.
     * appointment_id: Linked appointment (if any).
     * patient_name: Snapshot of the patient name for easier history reading.
     * action: Type of system event (e.g., BOOKED, ENCODED, VIEWED).
     * reason: The specific justification provided (from Reason-Gate modals).
     */
    protected $fillable = [
        'user_id', 
        'appointment_id', 
        'patient_name', 
        'action', 
        'reason'
    ];

    /**
     * Relationship: The user (performer) who triggered this log entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Static helper to record events across the system.
     * 
     * Usage: ActivityLog::record('VIEWED', 'Clinical Audit', $patient->name, $app->id);
     * 
     * @param string $action The action performed (automatically converted to uppercase).
     * @param string $reason The justification or description of the event.
     * @param string $patientName The name of the patient involved in the record.
     * @param int|null $appointmentId The ID of the related appointment, if applicable.
     * @return ActivityLog
     */
    public static function record($action, $reason = 'N/A', $patientName = 'SYSTEM', $appointmentId = null)
    {
        return self::create([
            /**
             * FIXED: Changed '?? 0' to '?? null'
             * Since the migration now allows nullable user_ids, passing null 
             * allows logs to be saved for system actions or deleted users 
             * without violating foreign key constraints.
             */
            'user_id' => Auth::id() ?? null, 
            'appointment_id' => $appointmentId,
            'patient_name' => strtoupper($patientName),
            'action' => strtoupper($action),
            'reason' => $reason
        ]);
    }
}