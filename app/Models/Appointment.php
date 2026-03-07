<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'appointment_date',
        'status', // 'pending', 'approved', 'returned'
        'return_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    /**
     * Get the user that owns the appointment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service requested for this appointment.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}