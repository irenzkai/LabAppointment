<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'description',
        'preparation',
        'estimated_time',
        'category', 
        'gender_restriction',
        'is_available',
    ];

    /**
     * Cast is_available to boolean and price to decimal
     */
    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * A service can be linked to many appointments.
     */
    public function appointments()
    {
        return $this->belongsToMany(Appointment::class, 'appointment_service');
    }

    /**
     * Dynamic Accessor to fetch compiled samples string for retro-compatibility.
     * Overrides missing 'sample_required' column read attempts.
     */
    public function getSampleRequiredAttribute()
    {
        $samples = DB::table('service_sample')
            ->join('samples', 'service_sample.sample_id', '=', 'samples.id')
            ->where('service_sample.service_id', $this->id)
            ->pluck('samples.name')
            ->toArray();

        return empty($samples) ? 'N/A' : implode(',', $samples);
    }

    /**
     * Helper to format minutes for display
     */
    public function getFormattedTimeAttribute() 
    {
        if ($this->estimated_time >= 60) {
            $hours = floor($this->estimated_time / 60);
            $mins = $this->estimated_time % 60;
            return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
        }
        return $this->estimated_time . ' mins';
    }
}