<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrganizationInvitationService;
use App\Models\TmpOrganizationUser;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class OrganizationInvitationController extends Controller
{
    protected $invitationService;

    public function __construct(OrganizationInvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Display invitations for an organization
     */
    public function index(Request $request, Organization $organization)
    {
        $status = $request->input('status');
        $invitations = $this->invitationService->getInvitations($organization->id, $status);
        $stats = $this->invitationService->getStats($organization->id);

        return view('admin.organizations.invitations.index', compact('organization', 'invitations', 'stats'));
    }

    /**
     * Show invitation form
     */
    public function create(Organization $organization)
    {
        return view('admin.organizations.invitations.create', compact('organization'));
    }

    /**
     * Send invitation
     */
    public function store(Request $request, Organization $organization): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,member,moderator',
            'message' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->invitationService->sendInvitation(
            $organization->id,
            $request->input('email'),
            $request->input('role'),
            auth()->id(),
            $request->input('message')
        );

        return response()->json($result);
    }

    /**
     * Resend invitation
     */
    public function resend(TmpOrganizationUser $invitation): JsonResponse
    {
        $result = $this->invitationService->resendInvitation($invitation->id);
        return response()->json($result);
    }

    /**
     * Cancel invitation
     */
    public function cancel(TmpOrganizationUser $invitation): JsonResponse
    {
        $result = $this->invitationService->cancelInvitation($invitation->id);
        return response()->json($result);
    }

    /**
     * Get invitation statistics
     */
    public function stats(Organization $organization): JsonResponse
    {
        $stats = $this->invitationService->getStats($organization->id);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Bulk send invitations
     */
    public function bulkSend(Request $request, Organization $organization): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invitations' => 'required|array|min:1|max:50',
            'invitations.*.email' => 'required|email|max:255',
            'invitations.*.role' => 'required|in:admin,member,moderator',
            'message' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $invitations = $request->input('invitations');
        $message = $request->input('message');
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($invitations as $invitationData) {
            $result = $this->invitationService->sendInvitation(
                $organization->id,
                $invitationData['email'],
                $invitationData['role'],
                auth()->id(),
                $message
            );

            $results[] = array_merge($result, [
                'email' => $invitationData['email'],
                'role' => $invitationData['role']
            ]);

            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => $successful > 0,
            'message' => "Bulk invitation completed: {$successful} successful, {$failed} failed",
            'results' => $results,
            'stats' => [
                'total' => count($invitations),
                'successful' => $successful,
                'failed' => $failed
            ]
        ]);
    }

    /**
     * Export invitations
     */
    public function export(Request $request, Organization $organization)
    {
        $status = $request->input('status');
        $invitations = $this->invitationService->getInvitations($organization->id, $status);

        $filename = "organization_{$organization->id}_invitations_" . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($invitations) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Email', 'Role', 'Status', 'Invited By', 'Invited At', 
                'Expires At', 'Accepted At', 'Rejected At', 'Message'
            ]);
            
            // Add data rows
            foreach ($invitations as $invitation) {
                fputcsv($file, [
                    $invitation->email,
                    $invitation->role,
                    $invitation->status,
                    $invitation->invitedBy ? $invitation->invitedBy->name : '',
                    $invitation->invitation_sent_at ? $invitation->invitation_sent_at->format('Y-m-d H:i:s') : '',
                    $invitation->expires_at ? $invitation->expires_at->format('Y-m-d H:i:s') : '',
                    $invitation->accepted_at ? $invitation->accepted_at->format('Y-m-d H:i:s') : '',
                    $invitation->rejected_at ? $invitation->rejected_at->format('Y-m-d H:i:s') : '',
                    $invitation->message ?: ''
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clean up expired invitations
     */
    public function cleanup(): JsonResponse
    {
        $cleaned = $this->invitationService->cleanupExpiredInvitations();
        
        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$cleaned} expired invitations"
        ]);
    }
}