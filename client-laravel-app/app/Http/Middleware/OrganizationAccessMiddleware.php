<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Organization;

class OrganizationAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $organization = $request->route('organization');
        
        if (!$organization instanceof Organization) {
            $organization = Organization::findOrFail($organization);
        }

        $user = auth()->user();

        // Check if user belongs to this organization
        if (!$user->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }

        // Add organization to request for easy access in controllers
        $request->merge(['current_organization' => $organization]);

        return $next($request);
    }
}