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
     * Get invitation by token
     *
     * @param string $token
     * @return TmpOrganizationUser|null
     */
    public function getInvitationByToken(string $token): ?TmpOrganizationUser
    {
        return TmpOrganizationUser::with(['organization', 'invitedBy'])
                                 ->where('invitation_token', $token)
                                 ->first();
    }
}