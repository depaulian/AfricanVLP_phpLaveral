<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\OrganizationInvitationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrganizationInvitationController extends Controller
{
    protected $invitationService;

    public function __construct(OrganizationInvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Display invitation response page
     */
    public function respond(Request $request, string $token)
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (!$invitation) {
            return view('client.invitations.invalid', [
                'message' => 'Invalid invitation token'
            ]);
        }

        if (!$invitation->isPending()) {
            return view('client.invitations.invalid', [
                'message' => 'This invitation is no longer valid',
                'invitation' => $invitation
            ]);
        }

        // Check if user is logged in and email matches
        $user = Auth::user();
        $emailMatches = $user && $user->email === $invitation->email;

        return view('client.invitations.respond', compact('invitation', 'user', 'emailMatches'));
    }

    /**
     * Accept invitation
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->invitationService->acceptInvitation($token, $userId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'redirect_url' => route('organizations.dashboard', $result['organization'])
            ]);
        }

        return response()->json($result);
    }

    /**
     * Reject invitation
     */
    public function reject(Request $request, string $token): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->invitationService->rejectInvitation($token, $userId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'redirect_url' => route('dashboard')
            ]);
        }

        return response()->json($result);
    }

    /**
     * Display user's invitations
     */
    public function myInvitations(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status');
        
        $invitations = $this->invitationService->getUserInvitations($user->email, $status);

        return view('client.invitations.index', compact('invitations'));
    }

    /**
     * Get user's pending invitations count
     */
    public function pendingCount(): JsonResponse
    {
        $user = Auth::user();
        $invitations = $this->invitationService->getUserInvitations($user->email, 'pending');

        return response()->json([
            'success' => true,
            'count' => $invitations->count()
        ]);
    }
}