<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. Import SoftDeletes trait
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes; // 2. Use SoftDeletes trait

    /**
     * The attributes that are mass assignable.
     * Normalized to 3NF: No physical 'name' or 'address' columns. [102]
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'phone', 
        'birthdate', 
        'sex', 
        'street', 
        'barangay', 
        'city', 
        'province', 
        'role', // 'user', 'staff', 'lab_tech', 'admin'
        'is_active',
        'password_change_required', // Added flag to force password updates on next login
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
            'password_change_required' => 'boolean', // Cast to boolean cleanly [102]
            'deleted_at' => 'datetime',             // Cast SoftDelete timestamp [102]
        ];
    }

    // =========================================================================
    // DYNAMIC ACCESSORS (COMPATIBILITY LAYER) [103]
    // =========================================================================

    /**
     * Dynamic Name Accessor (Compiles full name dynamically on-the-fly) [103]
     */
    public function getNameAttribute()
    {
        return $this->first_name . ($this->middle_name && strtoupper($this->middle_name) !== 'N/A' ? ' ' . $this->middle_name : '') . ' ' . $this->last_name;
    }

    /**
     * Dynamic Address Accessor (Compiles atomic fields into a single clinical string) [103]
     */
    public function getAddressAttribute()
    {
        return strtoupper("{$this->street}, BRGY. {$this->barangay}, {$this->city}, {$this->province}");
    }

    // =========================================================================
    // ROLE CHECKING HELPERS [103]
    // =========================================================================

    /**
     * Check if user is the System Administrator. [103]
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a Laboratory Technician (or Admin for oversight). [103]
     */
    public function isLabTech(): bool
    {
        return in_array($this->role, ['lab_tech', 'admin']);
    }

    /**
     * Check if user is a General Staff/Receptionist. [103]
     */
    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'lab_tech', 'admin']);
    }

    /**
     * Unified check for any employee (Internal Personnel). [103]
     */
    public function isEmployee(): bool
    {
        return in_array($this->role, ['staff', 'lab_tech', 'admin']);
    }

    /**
     * Check if user is purely a Patient. [104]
     */
    public function isPatient(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Specific check for workflow: Staff who are NOT technicians. [104]
     */
    public function isStaffOnly(): bool
    {
        return $this->role === 'staff';
    }

    // =========================================================================
    // RELATIONSHIPS [104]
    // =========================================================================

    /**
     * A patient can have multiple dependents (children/elderly). [104]
     */
    public function dependents()
    {
        return $this->hasMany(Dependent::class);
    }

    /**
     * A user (Patient) has many appointments. [104]
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Tracks actions performed by this user (Staff). [104]
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }
}