<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'organizations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'pending_name',
        'representative_name',
        'representative_phone',
        'status',
        'status_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * The authorized representative patient who created and manages this organization.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for clear domain naming.
     */
    public function representative(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Custom diagnostic packages proposed or active for this organization.
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'organization_id');
    }

    /**
     * Pre-recorded roster participants registered under this organization.
     */
    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, 'organization_id');
    }

    // =========================================================================
    // STATUS HELPERS
    // =========================================================================

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDeactivated(): bool
    {
        return $this->status === 'deactivated';
    }

    public function isPendingReactivation(): bool
    {
        return $this->status === 'pending_reactivation';
    }

    public function hasPendingNameChange(): bool
    {
        return !empty($this->pending_name);
    }

    // =========================================================================
    // DYNAMIC ACCESSORS & BACKWARD COMPATIBILITY
    // =========================================================================

    /**
     * Compatibility alias: organization_name -> name.
     */
    public function getOrganizationNameAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    /**
     * Compatibility alias: org_representative_name -> representative_name or user->name.
     */
    public function getOrgRepresentativeNameAttribute(): ?string
    {
        return $this->representative_name ?: ($this->user?->name ?? null);
    }

    /**
     * Compatibility alias: org_representative_phone -> representative_phone or user->phone.
     */
    public function getOrgRepresentativePhoneAttribute(): ?string
    {
        return $this->representative_phone ?: ($this->user?->phone ?? null);
    }

    /**
     * Compatibility alias: email -> user->email.
     */
    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    /**
     * Compatibility alias: phone -> representative_phone or user->phone.
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->representative_phone ?: ($this->user?->phone ?? null);
    }

    /**
     * Compatibility alias: organization_packages_count -> packages_count.
     */
    public function getOrganizationPackagesCountAttribute(): int
    {
        return $this->packages_count ?? ($this->relationLoaded('packages') ? $this->packages->count() : $this->packages()->count());
    }

    /**
     * Compatibility alias: organization_members_count -> members_count.
     */
    public function getOrganizationMembersCountAttribute(): int
    {
        return $this->members_count ?? ($this->relationLoaded('members') ? $this->members->count() : $this->members()->count());
    }
}