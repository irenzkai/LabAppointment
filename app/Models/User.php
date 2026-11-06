<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * Normalized to 3NF: No physical 'name' or 'address' columns.
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
        'occupation', // e.g. Medical Technologist, Pathologist, Radiologist, Attending Physician, Encoder, Receptionist
        'license_number', // PRC / DOH Professional License ID
        'is_recognized_org',
        'organization_name',
        'org_representative_name',
        'org_representative_phone',
        'org_status', // none, pending, approved, rejected
        'is_active',
        'password_change_required',
        'email_verified_at',
        'phone_verified_at',
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date', 
            'is_active' => 'boolean',
            'is_recognized_org' => 'boolean',
            'password_change_required' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    // =========================================================================
    // VERIFICATION COMPATIBILITY LAYER (EMAIL OR PHONE ACTIVATION)
    // =========================================================================

    /**
     * Determine if the user has verified their account (via Email OR Phone).
     * Satisfies Laravel's native 'verified' route middleware so that completing
     * either email or SMS verification unlocks the main menu and dashboard.
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at) || ! is_null($this->phone_verified_at);
    }

    /**
     * Check specifically if the email address itself has been verified.
     */
    public function hasVerifiedEmailOnly(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Check specifically if the mobile phone number has been verified.
     */
    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Determine if the account is verified across any valid channel.
     */
    public function isAccountVerified(): bool
    {
        return $this->hasVerifiedEmail();
    }

    /**
     * Mark the user's mobile phone as verified.
     */
    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    // =========================================================================
    // RECOGNIZED ORGANIZATION HELPERS
    // =========================================================================

    /**
     * Check if the user is a verified and approved recognized organization.
     * Evaluates active approved organizations first, falling back to profile flags.
     */
    public function isRecognizedOrg(): bool
    {
        if ($this->relationLoaded('organizations')) {
            return $this->organizations->where('status', 'approved')->isNotEmpty();
        }
        return $this->organizations()->where('status', 'approved')->exists()
            || ($this->is_recognized_org && $this->org_status === 'approved');
    }

    /**
     * Check if an organization partnership application is awaiting staff review.
     */
    public function hasPendingOrgApplication(): bool
    {
        if ($this->relationLoaded('organizations')) {
            return $this->organizations->whereIn('status', ['pending', 'pending_reactivation'])->isNotEmpty();
        }
        return $this->organizations()->whereIn('status', ['pending', 'pending_reactivation'])->exists()
            || ($this->org_status === 'pending');
    }

    /**
     * Check if the organization partnership is approved.
     */
    public function hasApprovedOrgStatus(): bool
    {
        return $this->isRecognizedOrg();
    }

    /**
     * Query scope to fetch only approved recognized organizations.
     */
    public function scopeRecognizedOrganizations(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('organizations', function ($sub) {
                $sub->where('status', 'approved');
            })->orWhere(function ($sub2) {
                $sub2->where('is_recognized_org', true)->where('org_status', 'approved');
            });
        });
    }

    /**
     * Query scope to fetch pending organization applications.
     */
    public function scopePendingOrganizations(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('organizations', function ($sub) {
                $sub->whereIn('status', ['pending', 'pending_reactivation']);
            })->orWhere('org_status', 'pending');
        });
    }

    // =========================================================================
    // DYNAMIC ACCESSORS (COMPATIBILITY LAYER - Multibyte Ñ/ñ Safe)
    // =========================================================================

    /**
     * Dynamic Name Accessor (Compiles full name dynamically on-the-fly, multibyte ñ -> Ñ safe).
     */
    public function getNameAttribute(): string
    {
        $mName = ($this->middle_name && mb_strtoupper($this->middle_name, 'UTF-8') !== 'N/A')
            ? ' ' . $this->middle_name : '';
        $fullName = $this->first_name . $mName . ' ' . $this->last_name;
        if ($this->suffix) {
            $fullName .= ' ' . $this->suffix;
        }
        return mb_strtoupper($fullName, 'UTF-8');
    }

    /**
     * Dynamic Address Accessor (Compiles atomic fields into a single clinical string).
     */
    public function getAddressAttribute(): string
    {
        return mb_strtoupper("{$this->street}, BRGY. {$this->barangay}, {$this->city}, {$this->province}", 'UTF-8');
    }

    /**
     * Dynamic Organization Name Accessor (Resolves primary approved entity name or legacy column).
     */
    public function getOrganizationNameAttribute()
    {
        if ($this->relationLoaded('organizations')) {
            $org = $this->organizations->firstWhere('status', 'approved') ?: $this->organizations->first();
            if ($org) return $org->name;
        } else {
            $orgName = $this->organizations()->where('status', 'approved')->value('name')
                ?? $this->organizations()->value('name');
            if ($orgName) return $orgName;
        }

        return $this->attributes['organization_name'] ?? '';
    }

    // =========================================================================
    // ROLE & OCCUPATION CHECKING HELPERS
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
    // CLINICAL PRACTITIONER QUERY SCOPES (Automates Workstation Signatories)
    // =========================================================================

    /**
     * Scope for Medical Technologists (PRC licensed laboratory personnel).
     */
    public function scopeMedicalTechnologists(Builder $query): Builder
    {
        return $query->whereIn('role', ['lab_tech', 'admin'])
            ->where(function ($q) {
                $q->where('occupation', 'LIKE', '%Medical Technologist%')
                  ->orWhere('occupation', 'LIKE', '%MedTech%');
            })
            ->whereNotNull('license_number');
    }

    /**
     * Scope for Pathologists.
     */
    public function scopePathologists(Builder $query): Builder
    {
        return $query->whereIn('role', ['lab_tech', 'admin'])
            ->where('occupation', 'LIKE', '%Pathologist%');
    }

    /**
     * Scope for Radiologists.
     */
    public function scopeRadiologists(Builder $query): Builder
    {
        return $query->whereIn('role', ['lab_tech', 'admin'])
            ->where('occupation', 'LIKE', '%Radiologist%');
    }

    /**
     * Scope for Attending Physicians (General Practitioners / Medical Officers).
     */
    public function scopePhysicians(Builder $query): Builder
    {
        return $query->whereIn('role', ['lab_tech', 'admin', 'staff'])
            ->where(function ($q) {
                $q->where('occupation', 'LIKE', '%Physician%')
                  ->orWhere('occupation', 'LIKE', '%Doctor%')
                  ->orWhere('occupation', 'LIKE', '%Medical Officer%');
            });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Organizations where this user is the registered representative.
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'user_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(Dependent::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    /**
     * Pre-recorded rosters of people (OJT Students, Employees, Faculty).
     */
    public function organizationMembers(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, 'user_id');
    }

    /**
     * Custom packages authored by this user/organization.
     */
    public function organizationPackages(): HasMany
    {
        return $this->hasMany(Package::class, 'organization_id');
    }
}