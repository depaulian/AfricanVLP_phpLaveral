<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Auditable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'address',
        'city_id',
        'country_id',
        'profile_image',
        'date_of_birth',
        'gender',
        'status',
        'is_admin',
        'admin_role',
        'admin_permissions',
        'created_by',
        'email_verified_at',
        'email_verification_token',
        'password_reset_token',
        'last_login',
        'login_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'password_reset_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'last_login' => 'datetime',
        'login_count' => 'integer',
        'is_admin' => 'boolean',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the city that the user belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country that the user belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the organizations that the user belongs to.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
                    ->withPivot('role', 'status', 'joined_date')
                    ->withTimestamps();
    }

    /**
     * Get the organizations where the user is an alumni.
     */
    public function alumniOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_alumni')
                    ->withPivot('status', 'graduation_year')
                    ->withTimestamps();
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user's email is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin && $this->admin_role === 'super_admin';
    }

    /**
     * Check if user has a specific admin role.
     */
    public function hasAdminRole(string $role): bool
    {
        return $this->is_admin && $this->admin_role === $role;
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->is_admin) {
            return false;
        }

        // Super admins have all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = json_decode($this->admin_permissions ?? '[]', true);
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * Get all admin permissions.
     */
    public function getAdminPermissions(): array
    {
        if (!$this->is_admin) {
            return [];
        }

        // Super admins have all permissions
        if ($this->isSuperAdmin()) {
            return ['*'];
        }

        return json_decode($this->admin_permissions ?? '[]', true);
    }

    /**
     * Get the admin role display name.
     */
    public function getAdminRoleDisplayAttribute(): string
    {
        $roles = [
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'moderator' => 'Moderator',
            'editor' => 'Editor',
            'viewer' => 'Viewer'
        ];

        return $roles[$this->admin_role] ?? 'Administrator';
    }

    /**
     * Get the user who created this admin.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}