<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TmpOrganizationUser extends Model
{


    protected $fillable = [
        'organization_id',
        'email',
        'role',
        'invited_by',
        'invitation_token',
        'invitation_sent_at',
        'expires_at',
        'accepted_at',
        'rejected_at',
        'status',
        'message'
    ];

    protected $casts = [
        'invitation_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    /**
     * Get the organization that owns the invitation
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who sent the invitation
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who was invited (if they exist)
     */
    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Generate invitation token
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if invitation is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Check if invitation is accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted' && !is_null($this->accepted_at);
    }

    /**
     * Check if invitation is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected' && !is_null($this->rejected_at);
    }

    /**
     * Accept the invitation
     */
    public function accept(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'accepted',
            'accepted_at' => now()
        ]);

        // Add user to organization
        $user = User::where('email', $this->email)->first();
        if ($user) {
            $user->organizations()->syncWithoutDetaching([
                $this->organization_id => [
                    'role' => $this->role,
                    'status' => 'active',
                    'joined_at' => now()
                ]
            ]);
        }

        return true;
    }

    /**
     * Reject the invitation
     */
    public function reject(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'rejected_at' => now()
        ]);

        return true;
    }

    /**
     * Get invitation URL
     */
    public function getInvitationUrl(): string
    {
        return route('organization.invitation.respond', [
            'token' => $this->invitation_token
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'accepted' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            'expired' => 'dark',
            default => 'primary'
        };
    }

    /**
     * Get time remaining until expiration
     */
    public function getTimeRemaining(): ?string
    {
        if (!$this->expires_at || $this->isExpired()) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }
}