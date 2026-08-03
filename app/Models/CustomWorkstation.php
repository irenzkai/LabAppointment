<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomWorkstation extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot function to automatically generate clean slugs from workstation names
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workstation) {
            $workstation->slug = Str::slug($workstation->name);
        });

        static::updating(function ($workstation) {
            $workstation->slug = Str::slug($workstation->name);
        });
    }

    /**
     * Relational connection to any active worksheets generated for appointments
     */
    public function results()
    {
        return $this->hasMany(CustomWorkstationResult::class);
    }
}