<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes trait [93]

class Dependent extends Model
{
    use SoftDeletes; // Use SoftDeletes trait [93]

    /**
     * The attributes that are mass assignable.
     * Normalized to 3NF: Decomposed name, suffix, and address fields [93]
     */
    protected $fillable = [
        'user_id', 
        'first_name',
        'middle_name',
        'last_name',
        'suffix', // Added to support optional minor suffixes (e.g., JR, III)
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
     * Get the attributes that should be cast to native types. [93]
     */
    protected $casts = [
        'birthdate' => 'date',
        'deleted_at' => 'datetime' // Cast soft delete timestamp [93]
    ];

    // =========================================================================
    // DYNAMIC ACCESSORS (COMPATIBILITY LAYER) [93]
    // =========================================================================

    /**
     * Dynamic Name Accessor (Compiles full name dynamically on-the-fly) [93]
     */
    public function getNameAttribute()
    {
        $fullName = $this->first_name . ($this->middle_name && strtoupper($this->middle_name) !== 'N/A' ? ' ' . $this->middle_name : '') . ' ' . $this->last_name;
        
        // Append suffix if it exists on the model instance
        if ($this->suffix) {
            $fullName .= ' ' . $this->suffix;
        }

        return $fullName;
    }

    /**
     * Dynamic Address Accessor (Compiles atomic fields into a single address string) [93]
     */
    public function getAddressAttribute()
    {
        return strtoupper("{$this->street}, BRGY. {$this->barangay}, {$this->city}, {$this->province}");
    }

    // =========================================================================
    // RELATIONSHIPS [94]
    // =========================================================================

    /**
     * Retrieve the parent parent user account associated with this dependent. [94]
     */
    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}