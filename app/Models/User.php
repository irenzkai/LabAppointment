<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * Normalized to 3NF: No physical 'name' or 'address' columns.
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
        ];
    }

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
     * Dynamic Address Accessor (Compiles atomic fields into a single clinical string)
     */
    public function getAddressAttribute()
    {
        return strtoupper("{$this->street}, BRGY. {$this->barangay}, {$this->city}, {$this->province}");
    }

    // =========================================================================
    // ROLE CHECKING HELPERS
    // =========================================================================

    /**
     * Check if user is the System Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a Laboratory Technician (or Admin for oversight).
     */
    public function isLabTech(): bool
    {
        return in_array($this->role, ['lab_tech', 'admin']);
    }

    /**
     * Check if user is a General Staff/Receptionist.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'lab_tech', 'admin']);
    }

    /**
     * Unified check for any employee (Internal Personnel).
     */
    public function isEmployee(): bool
    {
        return in_array($this->role, ['staff', 'lab_tech', 'admin']);
    }

    /**
     * Check if user is purely a Patient.
     */
    public function isPatient(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Specific check for workflow: Staff who are NOT technicians.
     */
    public function isStaffOnly(): bool
    {
        return $this->role === 'staff';
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * A patient can have multiple dependents (children/elderly).
     */
    public function dependents()
    {
        return $this->hasMany(Dependent::class);
    }

    /**
     * A user (Patient) has many appointments.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Tracks actions performed by this user (Staff).
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }
}