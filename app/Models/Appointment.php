<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = ['user_id', 'appointment_date', 'time_slot', 'status', 'return_reason'];

    protected $casts = ['appointment_date' => 'date'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function services() {
        return $this->belongsToMany(Service::class, 'appointment_service');
    }

    // Helper to calculate total of all tests in this appointment
    public function totalPrice() {
        return $this->services->sum('price');
    }
}