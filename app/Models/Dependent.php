<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dependent extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * Normalized to 3NF: Decomposed name, suffix, and address fields
     */
    protected $fillable = [
        'user_id', 
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
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
        'birthdate' => 'date',
        'deleted_at' => 'datetime'
    ];

    // =========================================================================
    // DYNAMIC ACCESSORS (COMPATIBILITY LAYER - Multibyte Ñ/ñ Safe)
    // =========================================================================

    /**
     * Dynamic Name Accessor (Compiles full name dynamically on-the-fly, multibyte ñ -> Ñ safe)
     */
    public function getNameAttribute()
    {
        $mName = ($this->middle_name && mb_strtoupper($this->middle_name, 'UTF-8') !== 'N/A') ? ' ' . $this->middle_name : '';
        $fullName = $this->first_name . $mName . ' ' . $this->last_name;
        
        if ($this->suffix) {
            $fullName .= ' ' . $this->suffix;
        }

        return mb_strtoupper($fullName, 'UTF-8');
    }

    /**
     * Dynamic Address Accessor (Compiles atomic fields into a single address string)
     */
    public function getAddressAttribute()
    {
        return mb_strtoupper("{$this->street}, BRGY. {$this->barangay}, {$this->city}, {$this->province}", 'UTF-8');
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}