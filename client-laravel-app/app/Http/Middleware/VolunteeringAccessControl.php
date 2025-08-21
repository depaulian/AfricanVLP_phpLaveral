<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VolunteeringAccessControl
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentication required'], 401);
            }
            return redirect()->route('login')->with('error', 'Please login to access volunteering features.');
        }

        $user = Auth::user();

        // Check specific permissions if provided
        if ($permission) {
            switch ($permission) {
                case 'create_opportunity':
                    if (!$user->organizations()->exists()) {
                        return $this->unauthorized($request, 'You must be part of an organization to create opportunities.');
                    }
                    break;

                case 'manage_organization':
                    $organizationId = $request->route('organization')?->id ?? $request->input('organization_id');
                    if ($organizationId && !$this->canManageOrganization($user, $organizationId)) {
                        return $this->unauthorized($request, 'You do not have permission to manage this organization.');
                    }
                    break;

                case 'supervise':
                    if (!$this->canSupervise($user)) {
                        return $this->unauthorized($request, 'You do not have supervisor permissions.');
                    }
                    break;

                case 'approve_hours':
                    $assignmentId = $request->route('assignment')?->id ?? $request->input('assignment_id');
                    if ($assignmentId && !$this->canApproveHours($user, $assignmentId)) {
                        return $this->unauthorized($request, 'You do not have permission to approve hours for this assignment.');
                    }
                    break;

                case 'view_analytics':
                    if (!$this->canViewAnalytics($user)) {
                        return $this->unauthorized($request, 'You do not have permission to view analytics.');
                    }
                    break;
            }
        }

        return $next($request);
    }

    /**
     * Check if user can manage organization
     */
    private function canManageOrganization($user, $organizationId): bool
    {
        $organizationUser = $user->organizations()
            ->where('organizations.id', $organizationId)
            ->first();

        return $organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager']);
    }

    /**
     * Check if user can supervise volunteers
     */
    private function canSupervise($user): bool
    {
        // Check if user has supervisor role in any organization
        $organizations = $user->organizations()->get();
        
        foreach ($organizations as $organization) {
            if (in_array($organization->pivot->role, ['admin', 'manager', 'coordinator', 'supervisor'])) {
                return true;
            }
        }

        // Check if user is assigned as supervisor for any assignments
        return $user->supervisedAssignments()->exists();
    }

    /**
     * Check if user can approve hours for specific assignment
     */
    private function canApproveHours($user, $assignmentId): bool
    {
        $assignment = \App\Models\VolunteerAssignment::find($assignmentId);
        
        if (!$assignment) {
            return false;
        }

        // Check if user is the supervisor
        if ($assignment->supervisor_id === $user->id) {
            return true;
        }

        // Check if user has organization permissions
        $organizationUser = $user->organizations()
            ->where('organizations.id', $assignment->application->opportunity->organization_id)
            ->first();

        return $organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager', 'coordinator']);
    }

    /**
     * Check if user can view analytics
     */
    private function canViewAnalytics($user): bool
    {
        // Users with organization roles can view analytics
        $organizations = $user->organizations()->get();
        
        foreach ($organizations as $organization) {
            if (in_array($organization->pivot->role, ['admin', 'manager'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return unauthorized response
     */
    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}