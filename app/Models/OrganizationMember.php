<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class OrganizationMember extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'organization_members';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'organization_id',
        'group_name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthdate',
        'sex',
        'phone',
        'email',
        'street',
        'barangay',
        'city',
        'province',
        'default_package_id',
        'default_service_ids',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'birthdate' => 'date:Y-m-d',
        'default_service_ids' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Automatically append dynamic accessors to JSON/Array representations.
     */
    protected $appends = [
        'name',
        'address',
        'age',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Relationship: The representative patient user account that registered this roster member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: The organization entity this member belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * Relationship: The default diagnostic package assigned to this member (if any).
     */
    public function defaultPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'default_package_id');
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    /**
     * Scope: Filter members by roster group category name (e.g. "OJT STUDENTS 2026").
     */
    public function scopeGroup(Builder $query, string $groupName): Builder
    {
        return $query->where('group_name', $groupName);
    }

    /**
     * Scope: Filter members belonging to a specific organization.
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where(function ($q) use ($organizationId) {
            $q->where('organization_id', $organizationId)
              ->orWhere('user_id', $organizationId);
        });
    }

    // =========================================================================
    // DYNAMIC ACCESSORS (Multibyte UTF-8 Safe)
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
        $parts = array_filter([
            $this->street,
            $this->barangay ? "BRGY. {$this->barangay}" : null,
            $this->city,
            $this->province
        ]);
        return !empty($parts) ? mb_strtoupper(implode(', ', $parts), 'UTF-8') : 'N/A';
    }

    /**
     * Calculate member's age based on birthdate.
     */
    public function getAgeAttribute(): string|int
    {
        return $this->birthdate ? Carbon::parse($this->birthdate)->age : 'N/A';
    }
}