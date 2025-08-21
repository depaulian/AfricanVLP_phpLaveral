<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('auth.login');
        }

        $user = auth()->user();
        $organizationId = $request->route('organization');

        // If organization ID is provided, check if user belongs to it
        if ($organizationId) {
            $hasAccess = $user->organizations()
                ->where('organizations.id', $organizationId)
                ->exists();

            if (!$hasAccess) {
                abort(403, 'Access denied. You do not belong to this organization.');
            }
        }

        return $next($request);
    }
}