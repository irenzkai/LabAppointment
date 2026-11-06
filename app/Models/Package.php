<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organization_id', // NULL = Global Medscreen Package; Non-null = Private Org Package
        'name',
        'price',
        'description',
        'preparation',
        'estimated_time',
        'gender_restriction',
        'is_available',
        'status', // 'pending', 'approved', 'declined', 'disabled'
        'status_reason',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    /**
     * Automatically append dynamic accessors to JSON/Array representations.
     */
    protected $appends = [
        'formatted_time',
        'sample_required',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Relationship: The recognized organization that authored this package (if private).
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * Relationship: Constituent atomic individual services bundled inside this package.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'package_service');
    }

    /**
     * Relationship: Appointments that have booked this package.
     */
    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_package');
    }

    /**
     * Relationship: Pre-recorded roster members assigned to this package by default.
     */
    public function organizationMembers(): HasMany
    {
        return $this->hasMany(OrganizationMember::class, 'default_package_id');
    }

    /**
     * Check if this package is a private package authored by an organization.
     */
    public function isCustomOrgPackage(): bool
    {
        return ! is_null($this->organization_id);
    }

    // =========================================================================
    // STATUS HELPERS & AUDIT CHECKS
    // =========================================================================

    /**
     * Check if package is pending clinic evaluation.
     */
    public function isPending(): bool
    {
        if ($this->trashed()) return false;
        if (!empty($this->status)) {
            return $this->status === 'pending';
        }
        return !$this->is_available && (empty($this->estimated_time) || is_null($this->price) || $this->price <= 0);
    }

    /**
     * Check if package has been approved and activated by clinic staff.
     */
    public function isApproved(): bool
    {
        if ($this->trashed()) return false;
        if (!empty($this->status)) {
            return $this->status === 'approved' && $this->is_available;
        }
        return (bool) $this->is_available;
    }

    /**
     * Check if package proposal was declined by clinic staff.
     */
    public function isDeclined(): bool
    {
        if ($this->trashed()) return false;
        return $this->status === 'declined';
    }

    /**
     * Check if package was disabled by clinic staff.
     */
    public function isDisabled(): bool
    {
        if ($this->trashed()) return false;
        if (!empty($this->status)) {
            return $this->status === 'disabled';
        }
        return !$this->is_available && $this->price > 0 && !is_null($this->estimated_time);
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    /**
     * Scope: Medscreen standard global packages available to all patients.
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Scope: Packages authored by a specific organization.
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope: Packages accessible for a specific user.
     * Includes all global packages, plus any organization-specific packages across all
     * approved organizations the user represents.
     */
    public function scopeAvailableFor(Builder $query, ?User $user = null): Builder
    {
        return $query->where('is_available', true)
            ->where(function ($q) use ($user) {
                $q->whereNull('organization_id');
                if ($user) {
                    // Fetch all approved organization IDs represented by the user
                    $approvedOrgIds = $user->organizations()
                        ->where('status', 'approved')
                        ->pluck('id')
                        ->toArray();

                    if (!empty($approvedOrgIds)) {
                        $q->orWhereIn('organization_id', $approvedOrgIds);
                    }

                    // Backward-compatibility fallback for legacy packages linked directly to user_id
                    if ($user->is_recognized_org && $user->org_status === 'approved') {
                        $q->orWhere('organization_id', $user->id);
                    }
                }
            });
    }

    // =========================================================================
    // DYNAMIC ACCESSORS
    // =========================================================================

    /**
     * Helper to format estimated minutes for display.
     * Returns 'N/A' if duration has not yet been designated by clinical staff.
     */
    public function getFormattedTimeAttribute(): string
    {
        if (empty($this->estimated_time)) {
            return 'N/A';
        }

        $time = (int) $this->estimated_time;
        if ($time >= 60) {
            $hours = floor($time / 60);
            $mins = $time % 60;
            return $hours . 'h ' . ($mins > 0 ? "{$mins}m" : '');
        }

        return "{$time} mins";
    }

    /**
     * Dynamic Accessor: Compiles unique biological specimen requirements
     * across all individual services bundled inside this package.
     */
    public function getSampleRequiredAttribute(): string
    {
        $services = $this->relationLoaded('services') ? $this->services : $this->services()->get();
        $samples = [];

        foreach ($services as $service) {
            $req = $service->sample_required;
            if (!empty($req) && $req !== 'N/A') {
                $parts = array_map('trim', explode(',', $req));
                foreach ($parts as $p) {
                    if (!empty($p) && !in_array($p, $samples)) {
                        $samples[] = $p;
                    }
                }
            }
        }

        return !empty($samples) ? implode(', ', $samples) : 'N/A';
    }
}