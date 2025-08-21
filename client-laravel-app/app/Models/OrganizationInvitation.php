<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationInvitation extends Model
{
    protected $table = 'organization_invitations';

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'organization_id',
        'email',
        'role',
        'message',
        'invited_by',
        'status',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the organization that owns the invitation.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Accept the invitation.
     */
    public function accept(User $user): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        // Add user to organization
        $this->organization->users()->attach($user->id, [
            'role' => $this->role,
            'status' => 'active',
            'joined_date' => now(),
        ]);

        // Update invitation status
        $this->update([
            'status' => 'accepted',
            'modified' => now(),
        ]);

        return true;
    }

    /**
     * Decline the invitation.
     */
    public function decline(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'declined',
            'modified' => now(),
        ]);

        return true;
    }

    /**
     * Get the status color for display.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => $this->isExpired() ? 'red' : 'yellow',
            'accepted' => 'green',
            'declined' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get the status text for display.
     */
    public function getStatusText(): string
    {
        if ($this->status === 'pending' && $this->isExpired()) {
            return 'Expired';
        }

        return match($this->status) {
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '<=', now());
    }
}