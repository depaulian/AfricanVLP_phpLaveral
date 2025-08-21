<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackSessionActivity
{
    public function __construct(
        private SecurityService $securityService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track for authenticated users
        if (Auth::check()) {
            $this->trackSessionActivity($request);
        }

        return $response;
    }

    /**
     * Track session activity for the authenticated user.
     */
    private function trackSessionActivity(Request $request): void
    {
        $user = Auth::user();
        $sessionId = session()->getId();

        // Find or create session record
        $session = UserSession::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            // Create new session record
            $this->securityService->createSession($user, $request, $sessionId);
        } else {
            // Update existing session activity
            $session->updateActivity();
        }
    }
}