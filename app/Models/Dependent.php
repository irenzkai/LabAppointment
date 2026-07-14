<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dependent extends Model
{
    /**
     * The attributes that are mass assignable.
     * Normalized to 3NF: Decomposed name and address fields.
     */
    protected $fillable = [
        'user_id', 
        'first_name',
        'middle_name',
        'last_name',
        'birthdate', 
        'sex', 
        'phone', 
        'relationship', 
        'street',
        'barangay',
        'city',
        'province'
    ];

    /**
     * Get the attributes that should be cast to native types.
     */
    protected $casts = [
        'birthdate' => 'date'
    ];

    // =========================================================================
    // DYNAMIC ACCESSORS (COMPATIBILITY LAYER)
    // =========================================================================

    /**
     * Dynamic Name Accessor (Compiles full name dynamically on-the-fly)
     */
    public function getNameAttribute()
    {
        return $this->first_name . ($this->middle_name && strtoupper($this->middle_name) !== 'N/A' ? ' ' . $this->middle_name : '') . ' ' . $this->last_name;
    }

    /**
     * Dynamic Address Accessor (Compiles atomic fields into a single address string)
     */
    public function getAddressAttribute()
    {
        return strtoupper("{$this->street}, BRGY. {$this->barangay}, {$this->city}, {$this->province}");
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Retrieve the parent user account associated with this dependent.
     */
    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}