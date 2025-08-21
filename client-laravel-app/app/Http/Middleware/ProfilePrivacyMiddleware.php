<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\UserProfile;
use Symfony\Component\HttpFoundation\Response;

class ProfilePrivacyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'view'): Response
    {
        $user = Auth::user();
        
        // Get profile from route parameter or request
        $profile = $this->getProfileFromRequest($request);
        
        if (!$profile) {
            abort(404, 'Profile not found');
        }

        // Check if user can perform the action
        if (!Gate::allows($action, $profile)) {
            abort(403, 'You do not have permission to access this profile information');
        }

        // Log profile access for security monitoring
        $this->logProfileAccess($user, $profile, $action, $request);

        return $next($request);
    }

    /**
     * Get profile from request parameters.
     */
    protected function getProfileFromRequest(Request $request): ?UserProfile
    {
        // Try to get profile from route parameters
        if ($request->route('profile')) {
            return $request->route('profile');
        }

        if ($request->route('user')) {
            $user = $request->route('user');
            return $user->profile;
        }

        // Try to get from request data
        if ($request->has('profile_id')) {
            return UserProfile::find($request->input('profile_id'));
        }

        if ($request->has('user_id')) {
            return UserProfile::where('user_id', $request->input('user_id'))->first();
        }

        // For API requests, try to get from authenticated user
        if ($request->is('api/*') && Auth::check()) {
            return Auth::user()->profile;
        }

        return null;
    }

    /**
     * Log profile access for security monitoring.
     */
    protected function logProfileAccess($accessor, UserProfile $profile, string $action, Request $request): void
    {
        // Only log if enabled in config
        if (!config('profile_privacy.security_settings.log_profile_access', true)) {
            return;
        }

        // Don't log users accessing their own profile
        if ($accessor && $accessor->id === $profile->user_id) {
            return;
        }

        // Create activity log entry
        activity()
            ->causedBy($accessor)
            ->performedOn($profile)
            ->withProperties([
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ])
            ->log("Profile {$action} access");

        // Check for suspicious activity
        $this->checkSuspiciousActivity($accessor, $profile, $request);
    }

    /**
     * Check for suspicious profile access patterns.
     */
    protected function checkSuspiciousActivity($accessor, UserProfile $profile, Request $request): void
    {
        if (!config('profile_privacy.security_settings.suspicious_activity_alerts', true)) {
            return;
        }

        // Skip checks for authenticated users
        if (!$accessor) {
            return;
        }

        // Check for rapid successive access
        $recentAccess = activity()
            ->causedBy($accessor)
            ->performedOn($profile)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentAccess > 10) {
            $this->flagSuspiciousActivity($accessor, $profile, 'rapid_access', [
                'access_count' => $recentAccess,
                'time_window' => '5 minutes',
            ]);
        }

        // Check for access from multiple IPs
        $uniqueIps = activity()
            ->causedBy($accessor)
            ->performedOn($profile)
            ->where('created_at', '>=', now()->subHour())
            ->get()
            ->pluck('properties.ip_address')
            ->unique()
            ->count();

        if ($uniqueIps > 3) {
            $this->flagSuspiciousActivity($accessor, $profile, 'multiple_ips', [
                'unique_ips' => $uniqueIps,
                'time_window' => '1 hour',
            ]);
        }
    }

    /**
     * Flag suspicious activity for review.
     */
    protected function flagSuspiciousActivity($accessor, UserProfile $profile, string $type, array $details): void
    {
        // Log the suspicious activity
        activity()
            ->causedBy($accessor)
            ->performedOn($profile)
            ->withProperties([
                'type' => $type,
                'details' => $details,
                'flagged_at' => now(),
            ])
            ->log('Suspicious profile access detected');

        // Notify profile owner if enabled
        if (config('profile_privacy.notifications.security_alert_notifications', true)) {
            $profile->user->notify(new \App\Notifications\SuspiciousProfileAccess($accessor, $type, $details));
        }

        // Notify administrators
        $admins = \App\Models\User::role(['admin', 'super_admin'])->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SuspiciousActivityAlert($accessor, $profile, $type, $details));
        }
    }
}