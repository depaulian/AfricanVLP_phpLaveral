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
            return redirect()->route('admin.login')->with('error', 'Please login to access admin features.');
        }

        $user = Auth::user();

        // Check specific permissions if provided
        if ($permission) {
            switch ($permission) {
                case 'manage_all':
                    if (!$user->hasRole(['admin', 'super_admin'])) {
                        return $this->unauthorized($request, 'You do not have permission to manage all volunteering features.');
                    }
                    break;

                case 'create_opportunity':
                    if (!$user->hasRole(['admin', 'super_admin']) && !$user->organizations()->exists()) {
                        return $this->unauthorized($request, 'You must be an admin or part of an organization to create opportunities.');
                    }
                    break;

                case 'manage_organization':
                    $organizationId = $request->route('organization')?->id ?? $request->input('organization_id');
                    if ($organizationId && !$this->canManageOrganization($user, $organizationId)) {
                        return $this->unauthorized($request, 'You do not have permission to manage this organization.');
                    }
                    break;

                case 'approve_opportunities':
                    if (!$user->hasRole(['admin', 'super_admin'])) {
                        return $this->unauthorized($request, 'You do not have permission to approve opportunities.');
                    }
                    break;

                case 'bulk_operations':
                    if (!$user->hasRole(['admin', 'super_admin'])) {
                        return $this->unauthorized($request, 'You do not have permission to perform bulk operations.');
                    }
                    break;

                case 'view_all_analytics':
                    if (!$user->hasRole(['admin', 'super_admin'])) {
                        return $this->unauthorized($request, 'You do not have permission to view all analytics.');
                    }
                    break;

                case 'export_data':
                    if (!$this->canExportData($user)) {
                        return $this->unauthorized($request, 'You do not have permission to export data.');
                    }
                    break;

                case 'system_settings':
                    if (!$user->hasRole('super_admin')) {
                        return $this->unauthorized($request, 'You do not have permission to modify system settings.');
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
        // Admin users can manage all organizations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        $organizationUser = $user->organizations()
            ->where('organizations.id', $organizationId)
            ->first();

        return $organizationUser && in_array($organizationUser->pivot->role, ['admin', 'manager']);
    }

    /**
     * Check if user can export data
     */
    private function canExportData($user): bool
    {
        // Admin users can export all data
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Organization admins can export their data
        $organizations = $user->organizations()->get();
        
        foreach ($organizations as $organization) {
            if ($organization->pivot->role === 'admin') {
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