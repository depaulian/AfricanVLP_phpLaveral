<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class OrganizationAdmin extends Model
{
    use HasFactory, SoftDeletes, Auditable;



    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'permissions',
        'assigned_by',
        'assigned_at',
        'expires_at',
        'is_active',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization this admin belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who is the admin
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who assigned this admin role
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if admin role is active
     */
    public function isActive(): bool
    {
        return $this->is_active && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if admin role is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get role color for UI
     */
    public function getRoleColorAttribute(): string
    {
        return match($this->role) {
            'super_admin' => 'red',
            'admin' => 'blue',
            'moderator' => 'green',
            'editor' => 'yellow',
            'viewer' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get role icon for UI
     */
    public function getRoleIconAttribute(): string
    {
        return match($this->role) {
            'super_admin' => 'shield-check',
            'admin' => 'user-cog',
            'moderator' => 'user-shield',
            'editor' => 'edit',
            'viewer' => 'eye',
            default => 'user',
        };
    }

    /**
     * Get formatted permissions list
     */
    public function getFormattedPermissionsAttribute(): string
    {
        if (!$this->permissions) {
            return 'No permissions';
        }

        if (in_array('*', $this->permissions)) {
            return 'All permissions';
        }

        return implode(', ', array_map('ucfirst', $this->permissions));
    }

    /**
     * Get time until expiration
     */
    public function getTimeUntilExpirationAttribute(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->expires_at->isPast()) {
            return 'Expired ' . $this->expires_at->diffForHumans();
        }

        return 'Expires ' . $this->expires_at->diffForHumans();
    }

    /**
     * Scope for active admins
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for inactive admins
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for expired admins
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope for admins by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for admins by organization
     */
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope for admins with specific permission
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->where(function ($q) use ($permission) {
            $q->whereJsonContains('permissions', $permission)
              ->orWhereJsonContains('permissions', '*');
        });
    }

    /**
     * Common admin roles
     */
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_EDITOR = 'editor';
    const ROLE_VIEWER = 'viewer';

    /**
     * Common permissions
     */
    const PERMISSION_ALL = '*';
    const PERMISSION_MANAGE_USERS = 'manage_users';
    const PERMISSION_MANAGE_CONTENT = 'manage_content';
    const PERMISSION_MANAGE_EVENTS = 'manage_events';
    const PERMISSION_MANAGE_RESOURCES = 'manage_resources';
    const PERMISSION_MANAGE_FORUMS = 'manage_forums';
    const PERMISSION_MANAGE_SETTINGS = 'manage_settings';
    const PERMISSION_VIEW_ANALYTICS = 'view_analytics';
    const PERMISSION_EXPORT_DATA = 'export_data';
    const PERMISSION_MODERATE_CONTENT = 'moderate_content';
    const PERMISSION_SEND_NOTIFICATIONS = 'send_notifications';

    /**
     * Get default permissions for role
     */
    public static function getDefaultPermissions(string $role): array
    {
        return match($role) {
            self::ROLE_SUPER_ADMIN => [self::PERMISSION_ALL],
            self::ROLE_ADMIN => [
                self::PERMISSION_MANAGE_USERS,
                self::PERMISSION_MANAGE_CONTENT,
                self::PERMISSION_MANAGE_EVENTS,
                self::PERMISSION_MANAGE_RESOURCES,
                self::PERMISSION_MANAGE_FORUMS,
                self::PERMISSION_VIEW_ANALYTICS,
                self::PERMISSION_EXPORT_DATA,
                self::PERMISSION_MODERATE_CONTENT,
                self::PERMISSION_SEND_NOTIFICATIONS,
            ],
            self::ROLE_MODERATOR => [
                self::PERMISSION_MANAGE_CONTENT,
                self::PERMISSION_MANAGE_EVENTS,
                self::PERMISSION_MANAGE_FORUMS,
                self::PERMISSION_MODERATE_CONTENT,
            ],
            self::ROLE_EDITOR => [
                self::PERMISSION_MANAGE_CONTENT,
                self::PERMISSION_MANAGE_EVENTS,
                self::PERMISSION_MANAGE_RESOURCES,
            ],
            self::ROLE_VIEWER => [
                self::PERMISSION_VIEW_ANALYTICS,
            ],
            default => [],
        };
    }

    /**
     * Get all available permissions
     */
    public static function getAllPermissions(): array
    {
        return [
            self::PERMISSION_ALL,
            self::PERMISSION_MANAGE_USERS,
            self::PERMISSION_MANAGE_CONTENT,
            self::PERMISSION_MANAGE_EVENTS,
            self::PERMISSION_MANAGE_RESOURCES,
            self::PERMISSION_MANAGE_FORUMS,
            self::PERMISSION_MANAGE_SETTINGS,
            self::PERMISSION_VIEW_ANALYTICS,
            self::PERMISSION_EXPORT_DATA,
            self::PERMISSION_MODERATE_CONTENT,
            self::PERMISSION_SEND_NOTIFICATIONS,
        ];
    }

    /**
     * Get all available roles
     */
    public static function getAllRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_MODERATOR,
            self::ROLE_EDITOR,
            self::ROLE_VIEWER,
        ];
    }
}
