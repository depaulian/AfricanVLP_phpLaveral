<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityService
{
    /**
     * Log a security event for a user.
     */
    public function logSecurityEvent(
        User $user,
        string $eventType,
        string $description,
        Request $request,
        string $risk = 'low',
        array $additional = []
    ): SecurityEvent {
        $ip = $request->ip();
        $ua = (string)($request->server('HTTP_USER_AGENT') ?? '');

        return SecurityEvent::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'event_description' => $description,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'risk_level' => $risk,
            'location_data' => null,
            'additional_data' => !empty($additional) ? $additional : null,
        ]);
    }

    /**
     * Create a session record for the user and detect suspicious activity.
     */
    public function createSession(User $user, Request $request, string $sessionId): UserSession
    {
        $ip = $request->ip();
        $ua = (string)($request->server('HTTP_USER_AGENT') ?? '');

        $session = UserSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'is_current' => true,
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        $this->maybeDetectSuspiciousActivity($user, $request);

        return $session;
    }

    /**
     * Basic password strength checker.
     */
    public function checkPasswordStrength(string $password): array
    {
        $feedback = [];
        $score = 0;

        $length = strlen($password);
        $hasLower = (bool)preg_match('/[a-z]/', $password);
        $hasUpper = (bool)preg_match('/[A-Z]/', $password);
        $hasDigit = (bool)preg_match('/\d/', $password);
        $hasSpecial = (bool)preg_match('/[^A-Za-z0-9]/', $password);

        if ($length >= 8) $score++;
        if ($hasLower && $hasUpper) $score++;
        if ($hasDigit) $score++;
        if ($hasSpecial) $score++;
        if ($length >= 12 && $score >= 3) $score = min(4, $score + 1);

        if ($length < 8) $feedback[] = 'Use at least 8 characters.';
        if (!$hasLower || !$hasUpper) $feedback[] = 'Mix upper and lower case letters.';
        if (!$hasDigit) $feedback[] = 'Add numbers.';
        if (!$hasSpecial) $feedback[] = 'Add special characters.';

        $strength = 'weak';
        if ($score >= 4 && $length >= 12) {
            $strength = 'strong';
        } elseif ($score >= 2) {
            $strength = 'medium';
        }

        // Keep feedback short for strong passwords
        if ($strength === 'strong') {
            $feedback = array_slice($feedback, 0, 1);
        }

        return [
            'strength' => $strength,
            'score' => $score,
            'feedback' => $feedback,
        ];
    }

    /**
     * Generate N unique backup codes (format: AAAA-BBBB alphanumeric upper-case).
     */
    public function generateBackupCodes(int $count = 5): array
    {
        $codes = [];
        while (count($codes) < $count) {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4)) . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
            // Normalize: only A-Z0-9
            $code = preg_replace('/[^A-Z0-9-]/', '', $code);
            $codes[$code] = true;
        }
        return array_keys($codes);
    }

    /**
     * Return security recommendations for a user.
     */
    public function getSecurityRecommendations(User $user): array
    {
        $recs = [];

        // Recommend enabling 2FA if not set (assumes optional two_factor_secret column)
        $has2fa = (bool)($user->two_factor_secret ?? false);
        if (!$has2fa) {
            $recs[] = [
                'type' => 'two_factor',
                'message' => 'Enable two-factor authentication to protect your account.',
                'priority' => 'high',
            ];
        }

        // Recommend reviewing active sessions if many
        $activeCount = UserSession::where('user_id', $user->id)->active()->count();
        if ($activeCount > 3) {
            $recs[] = [
                'type' => 'sessions_review',
                'message' => 'You have multiple active sessions. Review and terminate unknown sessions.',
                'priority' => 'medium',
            ];
        }

        return $recs;
    }

    /**
     * Terminate a specific session for a user.
     */
    public function terminateSession(User $user, string $sessionId): bool
    {
        $session = UserSession::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            return false;
        }

        $session->is_current = false;
        $session->expires_at = now();
        $session->save();

        return true;
    }

    /**
     * Terminate all sessions except the current session ID.
     * Returns the number of sessions terminated.
     */
    public function terminateAllOtherSessions(User $user, string $currentSessionId): int
    {
        return UserSession::where('user_id', $user->id)
            ->where('session_id', '!=', $currentSessionId)
            ->active()
            ->update([
                'is_current' => false,
                'expires_at' => now(),
            ]);
    }

    /**
     * Delete old low-risk events (older than 6 months) and expired sessions (older than 30 days).
     */
    public function cleanupOldData(): array
    {
        $deletedEvents = SecurityEvent::where('risk_level', 'low')
            ->where('created_at', '<', now()->subMonths(6))
            ->delete();

        $deletedSessions = UserSession::expired()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        return [
            'deleted_events' => $deletedEvents,
            'deleted_sessions' => $deletedSessions,
        ];
    }

    /**
     * Determine if an account should be considered locked due to many failed attempts.
     */
    public function isAccountLocked(User $user): bool
    {
        $windowStart = now()->subMinutes(60);
        $failed = SecurityEvent::where('user_id', $user->id)
            ->where('event_type', 'login_failed')
            ->where('created_at', '>=', $windowStart)
            ->count();
        return $failed >= 5;
    }

    /**
     * Create a high/critical suspicious activity event when heuristics match.
     */
    protected function maybeDetectSuspiciousActivity(User $user, Request $request): void
    {
        $ip = $request->ip();
        $ua = (string)($request->server('HTTP_USER_AGENT') ?? '');

        $activeCount = UserSession::where('user_id', $user->id)->active()->count();
        $lastSession = UserSession::where('user_id', $user->id)->latest('created_at')->first();

        $factors = [];
        if ($activeCount >= 4) {
            $factors[] = 'Multiple concurrent sessions';
        }
        if ($lastSession && ($lastSession->ip_address !== $ip)) {
            $factors[] = 'New IP address';
        }
        if ($lastSession && ($lastSession->user_agent !== $ua)) {
            $factors[] = 'New device or browser';
        }

        if (!empty($factors)) {
            $this->logSecurityEvent(
                $user,
                'suspicious_activity',
                'Suspicious activity detected',
                $request,
                'high',
                ['factors' => $factors]
            );
        }
    }
}
