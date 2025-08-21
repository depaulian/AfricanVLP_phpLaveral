<?php

namespace App\Services;

use App\Models\TmpOrganizationUser;
use App\Models\Organization;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrganizationInvitationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send invitation to join organization
     *
     * @param int $organizationId
     * @param string $email
     * @param string $role
     * @param int $invitedBy
     * @param string|null $message
     * @return array
     */
    public function sendInvitation(int $organizationId, string $email, string $role, int $invitedBy, ?string $message = null): array
    {
        try {
            // Check if organization exists
            $organization = Organization::find($organizationId);
            if (!$organization) {
                return [
                    'success' => false,
                    'message' => 'Organization not found'
                ];
            }

            // Check if user is already a member
            $existingMember = User::where('email', $email)
                                 ->whereHas('organizations', function($query) use ($organizationId) {
                                     $query->where('organization_id', $organizationId);
                                 })
                                 ->first();

            if ($existingMember) {
                return [
                    'success' => false,
                    'message' => 'User is already a member of this organization'
                ];
            }

            // Check if there's already a pending invitation
            $existingInvitation = TmpOrganizationUser::where('organization_id', $organizationId)
                                                   ->where('email', $email)
                                                   ->pending()
                                                   ->first();

            if ($existingInvitation) {
                return [
                    'success' => false,
                    'message' => 'There is already a pending invitation for this email',
                    'invitation' => $existingInvitation
                ];
            }

            // Create invitation
            $invitation = TmpOrganizationUser::create([
                'organization_id' => $organizationId,
                'email' => $email,
                'role' => $role,
                'invited_by' => $invitedBy,
                'invitation_token' => TmpOrganizationUser::generateToken(),
                'invitation_sent_at' => now(),
                'expires_at' => now()->addDays(7),
                'status' => 'pending',
                'message' => $message
            ]);

            // Send invitation email
            $this->sendInvitationEmail($invitation);

            // Create notification for existing user if they exist
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $this->notificationService->create(
                    $existingUser,
                    'organization',
                    'Organization Invitation',
                    "You've been invited to join {$organization->name}",
                    [
                        'action_url' => $invitation->getInvitationUrl(),
                        'action_text' => 'View Invitation',
                        'data' => [
                            'organization_id' => $organizationId,
                            'invitation_id' => $invitation->id,
                            'role' => $role
                        ]
                    ]
                );
            }

            return [
                'success' => true,
                'message' => 'Invitation sent successfully',
                'invitation' => $invitation
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send organization invitation: ' . $e->getMessage(), [
                'organization_id' => $organizationId,
                'email' => $email,
                'role' => $role,
                'invited_by' => $invitedBy
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send invitation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Accept invitation
     *
     * @param string $token
     * @param int|null $userId
     * @return array
     */
    public function acceptInvitation(string $token, ?int $userId = null): array
    {
        try {
            $invitation = TmpOrganizationUser::where('invitation_token', $token)->first();

            if (!$invitation) {
                return [
                    'success' => false,
                    'message' => 'Invalid invitation token'
                ];
            }

            if (!$invitation->isPending()) {
                return [
                    'success' => false,
                    'message' => 'Invitation is no longer valid',
                    'invitation' => $invitation
                ];
            }

            // If user ID is provided, verify it matches the invitation email
            if ($userId) {
                $user = User::find($userId);
                if (!$user || $user->email !== $invitation->email) {
                    return [
                        'success' => false,
                        'message' => 'User email does not match invitation'
                    ];
                }
            }

            DB::beginTransaction();

            // Accept the invitation
            $accepted = $invitation->accept();

            if (!$accepted) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to accept invitation'
                ];
            }

            // Send notification to the person who sent the invitation
            $inviter = $invitation->invitedBy;
            if ($inviter) {
                $this->notificationService->create(
                    $inviter,
                    'organization',
                    'Invitation Accepted',
                    "{$invitation->email} has accepted your invitation to join {$invitation->organization->name}",
                    [
                        'data' => [
                            'organization_id' => $invitation->organization_id,
                            'invitation_id' => $invitation->id,
                            'accepted_email' => $invitation->email
                        ]
                    ]
                );
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Invitation accepted successfully',
                'invitation' => $invitation,
                'organization' => $invitation->organization
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to accept organization invitation: ' . $e->getMessage(), [
                'token' => $token,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to accept invitation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reject invitation
     *
     * @param string $token
     * @param int|null $userId
     * @return array
     */
    public function rejectInvitation(string $token, ?int $userId = null): array
    {
        try {
            $invitation = TmpOrganizationUser::where('invitation_token', $token)->first();

            if (!$invitation) {
                return [
                    'success' => false,
                    'message' => 'Invalid invitation token'
                ];
            }

            if (!$invitation->isPending()) {
                return [
                    'success' => false,
                    'message' => 'Invitation is no longer valid',
                    'invitation' => $invitation
                ];
            }

            // If user ID is provided, verify it matches the invitation email
            if ($userId) {
                $user = User::find($userId);
                if (!$user || $user->email !== $invitation->email) {
                    return [
                        'success' => false,
                        'message' => 'User email does not match invitation'
                    ];
                }
            }

            // Reject the invitation
            $rejected = $invitation->reject();

            if (!$rejected) {
                return [
                    'success' => false,
                    'message' => 'Failed to reject invitation'
                ];
            }

            // Send notification to the person who sent the invitation
            $inviter = $invitation->invitedBy;
            if ($inviter) {
                $this->notificationService->create(
                    $inviter,
                    'organization',
                    'Invitation Rejected',
                    "{$invitation->email} has declined your invitation to join {$invitation->organization->name}",
                    [
                        'data' => [
                            'organization_id' => $invitation->organization_id,
                            'invitation_id' => $invitation->id,
                            'rejected_email' => $invitation->email
                        ]
                    ]
                );
            }

            return [
                'success' => true,
                'message' => 'Invitation rejected successfully',
                'invitation' => $invitation
            ];

        } catch (\Exception $e) {
            Log::error('Failed to reject organization invitation: ' . $e->getMessage(), [
                'token' => $token,
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reject invitation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Resend invitation
     *
     * @param int $invitationId
     * @return array
     */
    public function resendInvitation(int $invitationId): array
    {
        try {
            $invitation = TmpOrganizationUser::find($invitationId);

            if (!$invitation) {
                return [
                    'success' => false,
                    'message' => 'Invitation not found'
                ];
            }

            if ($invitation->isAccepted() || $invitation->isRejected()) {
                return [
                    'success' => false,
                    'message' => 'Cannot resend a completed invitation'
                ];
            }

            // Resend the invitation
            $resent = $invitation->resend();

            if (!$resent) {
                return [
                    'success' => false,
                    'message' => 'Failed to resend invitation'
                ];
            }

            // Send invitation email again
            $this->sendInvitationEmail($invitation);

            return [
                'success' => true,
                'message' => 'Invitation resent successfully',
                'invitation' => $invitation
            ];

        } catch (\Exception $e) {
            Log::error('Failed to resend organization invitation: ' . $e->getMessage(), [
                'invitation_id' => $invitationId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to resend invitation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel invitation
     *
     * @param int $invitationId
     * @return array
     */
    public function cancelInvitation(int $invitationId): array
    {
        try {
            $invitation = TmpOrganizationUser::find($invitationId);

            if (!$invitation) {
                return [
                    'success' => false,
                    'message' => 'Invitation not found'
                ];
            }

            if ($invitation->isAccepted()) {
                return [
                    'success' => false,
                    'message' => 'Cannot cancel an accepted invitation'
                ];
            }

            // Cancel the invitation
            $cancelled = $invitation->cancel();

            if (!$cancelled) {
                return [
                    'success' => false,
                    'message' => 'Failed to cancel invitation'
                ];
            }

            return [
                'success' => true,
                'message' => 'Invitation cancelled successfully',
                'invitation' => $invitation
            ];

        } catch (\Exception $e) {
            Log::error('Failed to cancel organization invitation: ' . $e->getMessage(), [
                'invitation_id' => $invitationId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel invitation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get invitations for organization
     *
     * @param int $organizationId
     * @param string|null $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInvitations(int $organizationId, ?string $status = null)
    {
        $query = TmpOrganizationUser::with(['organization', 'invitedBy', 'invitedUser'])
                                   ->where('organization_id', $organizationId);

        if ($status) {
            if ($status === 'pending') {
                $query->pending();
            } else {
                $query->where('status', $status);
            }
        }

        return $query->orderBy('created', 'desc')->get();
    }

    /**
     * Get invitations for user
     *
     * @param string $email
     * @param string|null $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserInvitations(string $email, ?string $status = null)
    {
        $query = TmpOrganizationUser::with(['organization', 'invitedBy'])
                                   ->where('email', $email);

        if ($status) {
            if ($status === 'pending') {
                $query->pending();
            } else {
                $query->where('status', $status);
            }
        }

        return $query->orderBy('created', 'desc')->get();
    }

    /**
     * Clean up expired invitations
     *
     * @return int
     */
    public function cleanupExpiredInvitations(): int
    {
        return TmpOrganizationUser::expired()
                                 ->where('status', 'pending')
                                 ->update(['status' => 'expired']);
    }

    /**
     * Send invitation email
     *
     * @param TmpOrganizationUser $invitation
     * @return void
     */
    protected function sendInvitationEmail(TmpOrganizationUser $invitation): void
    {
        try {
            Mail::send('emails.organization.invitation', [
                'invitation' => $invitation,
                'organization' => $invitation->organization,
                'inviter' => $invitation->invitedBy,
                'invitation_url' => $invitation->getInvitationUrl()
            ], function ($message) use ($invitation) {
                $message->to($invitation->email)
                        ->subject("Invitation to join {$invitation->organization->name}");
            });

        } catch (\Exception $e) {
            Log::error('Failed to send invitation email: ' . $e->getMessage(), [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email
            ]);
        }
    }

    /**
     * Get invitation statistics
     *
     * @param int|null $organizationId
     * @return array
     */
    public function getStats(?int $organizationId = null): array
    {
        $query = TmpOrganizationUser::query();

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return [
            'total_invitations' => $query->count(),
            'pending_invitations' => $query->pending()->count(),
            'accepted_invitations' => $query->accepted()->count(),
            'rejected_invitations' => $query->rejected()->count(),
            'expired_invitations' => $query->expired()->count(),
            'invitations_this_month' => $query->whereMonth('created', now()->month)->count(),
            'acceptance_rate' => $this->calculateAcceptanceRate($organizationId)
        ];
    }

    /**
     * Calculate acceptance rate
     *
     * @param int|null $organizationId
     * @return float
     */
    protected function calculateAcceptanceRate(?int $organizationId = null): float
    {
        $query = TmpOrganizationUser::query();

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $totalCompleted = $query->whereIn('status', ['accepted', 'rejected'])->count();
        $accepted = $query->accepted()->count();

        return $totalCompleted > 0 ? round(($accepted / $totalCompleted) * 100, 2) : 0;
    }
}