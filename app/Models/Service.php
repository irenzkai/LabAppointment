<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
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
     * Cast attributes to native types.
     */
    protected $casts = [
        'is_available' => 'boolean',
        'price'        => 'decimal:2',
        'deleted_at'   => 'datetime',
    ];

    /**
     * Automatically append dynamic accessors to JSON/Array representations.
     */
    protected $appends = [
        'sample_required',
        'formatted_time',
    ];

    /**
     * A service can be linked to many appointments.
     */
    public function appointments()
    {
        return $this->belongsToMany(Appointment::class, 'appointment_service');
    }

    /**
     * Dynamic Accessor to fetch compiled samples string with clinical catalog fallback.
     */
    public function getSampleRequiredAttribute(): string
    {
        try {
            $samples = DB::table('service_sample')
                ->join('samples', 'service_sample.sample_id', '=', 'samples.id')
                ->where('service_sample.service_id', $this->id)
                ->pluck('samples.name')
                ->toArray();

            if (!empty($samples)) {
                return implode(', ', $samples);
            }
        } catch (\Throwable $e) {
            // Fallback gracefully if pivot table is unavailable
        }

        // Clinical catalog fallback matching laboratory defaults
        $name = strtoupper($this->name);
        if (str_contains($name, 'URINE') || str_contains($name, 'URINALYSIS') || str_contains($name, 'DRUG TEST')) {
            return 'Urine';
        }
        if (str_contains($name, 'STOOL') || str_contains($name, 'FECALYSIS')) {
            return 'Stool';
        }
        if (str_contains($name, 'PREGNANCY TEST')) {
            return 'Urine';
        }
        if (str_contains($name, 'PEDIA') || str_contains($name, 'PREGNANCY PACKAGE')) {
            return 'Blood, Urine';
        }
        if (str_contains($name, 'X-RAY') || str_contains($name, 'XRAY') || str_contains($name, 'ECG') || str_contains($name, 'MEDICAL CERTIFICATE')) {
            return 'N/A';
        }

        return 'Blood';
    }

    /**
     * Helper to format minutes for display.
     */
    public function getFormattedTimeAttribute(): string
    {
        if ($this->estimated_time >= 60) {
            $hours = floor($this->estimated_time / 60);
            $mins = $this->estimated_time % 60;
            return $hours . 'h ' . ($mins > 0 ? "{$mins}m" : '');
        }
        return ($this->estimated_time ?: 5) . ' mins';
    }
}