<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentConfig extends Model
{
    protected $fillable = [
        'opening_time', 
        'closing_time', 
        'slot_duration', 
        'max_patients_per_slot',
        'has_lunch_break', 
        'lunch_start', 
        'lunch_end'
    ];
}
