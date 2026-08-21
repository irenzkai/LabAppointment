<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * Normalized to 3NF: No physical 'name' or 'address' columns
     */
    protected $fillable = [
        'first_name', 
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'password',
        'phone', 
        'birthdate', 
        'sex', 
        'street', 
        'barangay', 
        'city', 
        'province', 
        'role',
        'is_active',
        'password_change_required',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast to native types.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date', 
            'is_active' => 'boolean',
            'password_change_required' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

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
     * Dynamic Address Accessor (Compiles atomic fields into a single clinical string)
     */
    public function getAddressAttribute()
    {
        return mb_strtoupper("{$this->street}, BRGY. {$this->barangay}, {$this->city}, {$this->province}", 'UTF-8');
    }

    // =========================================================================
    // ROLE CHECKING HELPERS
    // =========================================================================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLabTech(): bool
    {
        return in_array($this->role, ['lab_tech', 'admin']);
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'lab_tech', 'admin']);
    }

    public function isEmployee(): bool
    {
        return in_array($this->role, ['staff', 'lab_tech', 'admin']);
    }

    public function isPatient(): bool
    {
        return $this->role === 'user';
    }

    public function isStaffOnly(): bool
    {
        return $this->role === 'staff'; 
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function dependents()
    {
        return $this->hasMany(Dependent::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }
}