<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentConfig extends Model
{
    /**
     * The attributes that are mass assignable.
     * 
     * day_of_week: 0 (Sun) to 6 (Sat) for recurring rules.
     * specific_date: YYYY-MM-DD for one-off overrides (holidays).
     */
    protected $fillable = [
        'day_of_week', 
        'specific_date', 
        'is_open', 
        'opening_time', 
        'closing_time', 
        'slot_duration', 
        'has_lunch_break', 
        'lunch_start', 
        'lunch_end', 
        'max_patients_per_slot', 
        'lead_time_hours'
    ];

    /**
     * The attributes that should be cast.
     * Prevents "1" vs true issues in Blade and Controllers.
     */
    protected $casts = [
        'is_open' => 'boolean',
        'has_lunch_break' => 'boolean',
        'slot_duration' => 'integer',
        'max_patients_per_slot' => 'integer',
        'lead_time_hours' => 'integer',
        'day_of_week' => 'integer',
        'specific_date' => 'date',
    ];

    /**
     * Get the effective configuration for a given date.
     * 
     * PRIORITIZATION:
     * 1. Check if a specific date override exists (e.g., a holiday or special schedule).
     * 2. If none, fallback to the standard recurring rule for that day of the week.
     * 
     * @param string $date (Format: YYYY-MM-DD)
     * @return AppointmentConfig|null
     */
    public static function getEffectiveConfig($date) {
        $dayOfWeek = date('w', strtotime($date));
        
        return self::where('specific_date', $date)->first() 
            ?? self::where('day_of_week', $dayOfWeek)->first();
    }

    /**
     * Helper to check if a specific time falls within the configured lunch break.
     * 
     * @param string $time (Format: HH:MM:SS)
     * @return bool
     */
    public function isLunchTime($time): bool
    {
        if (!$this->has_lunch_break || !$this->lunch_start || !$this->lunch_end) {
            return false;
        }

        return ($time >= $this->lunch_start && $time < $this->lunch_end);
    }
}