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
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone', 
        'birthdate', 
        'sex', 
        'address', 
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
     * Get the attributes that should be cast.
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

    /*
    |--------------------------------------------------------------------------
    | ROLE CHECKING HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user is the System Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a Laboratory Technician.
     * Inherited by Admin for oversight.
     */
    public function isLabTech(): bool
    {
        return in_array($this->role, ['lab_tech', 'admin']);
    }

    /**
     * Check if user is a General Staff/Receptionist.
     * Inherited by Lab Techs and Admins so they can access front-end lists.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'lab_tech', 'admin']);
    }

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
     * Useful for UI logic where you want to hide clinical buttons from receptionists.
     */
    public function isStaffOnly(): bool
    {
        return $this->role === 'staff';
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

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
     * For Audit Logging: Tracks actions performed by this user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}