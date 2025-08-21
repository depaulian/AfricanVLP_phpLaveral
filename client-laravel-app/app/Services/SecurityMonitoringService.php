<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class SecurityMonitoringService
{
    private const CACHE_PREFIX = 'security_monitoring:';
    private const ALERT_COOLDOWN = 3600; // 1 hour

    /**
     * Record a security event.
     */
    public function recordEvent(string $type, array $data = []): void
    {
        $event = [
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'data' => $data,
        ];

        // Log the event
        Log::channel('security')->info("Security event: {$type}", $event);

        // Update counters
        $this->updateCounters($type);

        // Check if we need to send alerts
        $this->checkAlertThresholds($type);
    }

    /**
     * Update event counters.
     */
    private function updateCounters(string $type): void
    {
        $hourKey = self::CACHE_PREFIX . 'hourly:' . $type . ':' . now()->format('Y-m-d-H');
        $dailyKey = self::CACHE_PREFIX . 'daily:' . $type . ':' . now()->format('Y-m-d');
        
        Cache::increment($hourKey, 1);
        Cache::increment($dailyKey, 1);
        
        // Set expiration
        Cache::put($hourKey, Cache::get($hourKey, 0), now()->addHours(25));
        Cache::put($dailyKey, Cache::get($dailyKey, 0), now()->addDays(8));
    }

    /**
     * Check if alert thresholds are exceeded.
     */
    private function checkAlertThresholds(string $type): void
    {
        if (!config('security.monitoring.enabled')) {
            return;
        }

        $thresholds = $this->getAlertThresholds();
        $hourKey = self::CACHE_PREFIX . 'hourly:' . $type . ':' . now()->format('Y-m-d-H');
        $count = Cache::get($hourKey, 0);

        if (isset($thresholds[$type]) && $count >= $thresholds[$type]) {
            $this->sendAlert($type, $count);
        }
    }

    /**
     * Get alert thresholds for different event types.
     */
    private function getAlertThresholds(): array
    {
        return [
            'failed_login' => 10,
            'rate_limit_exceeded' => 20,
            'sql_injection_attempt' => 1,
            'xss_attempt' => 1,
            'file_upload_violation' => 5,
            'csrf_token_mismatch' => 10,
            'suspicious_activity' => 5,
            'brute_force_attempt' => 5,
            'unauthorized_access' => 3,
            'spam_attempt' => 10,
            'profile_abuse' => 5,
        ];
    }

    /**
     * Send security alert.
     */
    private function sendAlert(string $type, int $count): void
    {
        $alertKey = self::CACHE_PREFIX . 'alert_sent:' . $type . ':' . now()->format('Y-m-d-H');
        
        // Check if we've already sent an alert for this type in this hour
        if (Cache::has($alertKey)) {
            return;
        }

        // Mark alert as sent
        Cache::put($alertKey, true, self::ALERT_COOLDOWN);

        $message = "Security Alert: {$count} {$type} events detected in the last hour.";
        
        // Send email notification
        if ($email = config('security.monitoring.notification_email')) {
            try {
                Mail::raw($message, function ($mail) use ($email, $type) {
                    $mail->to($email)
                         ->subject("Security Alert: {$type}");
                });
            } catch (\Exception $e) {
                Log::error('Failed to send security alert email', ['error' => $e->getMessage()]);
            }
        }

        // Send Slack notification
        if ($webhook = config('security.monitoring.slack_webhook')) {
            try {
                Http::post($webhook, [
                    'text' => $message,
                    'channel' => '#security-alerts',
                    'username' => 'Security Monitor',
                    'icon_emoji' => ':warning:',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send security alert to Slack', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Get security statistics.
     */
    public function getStatistics(string $period = 'hourly'): array
    {
        $stats = [];
        $eventTypes = [
            'failed_login',
            'rate_limit_exceeded',
            'sql_injection_attempt',
            'xss_attempt',
            'file_upload_violation',
            'csrf_token_mismatch',
            'suspicious_activity',
            'spam_attempt',
            'profile_abuse',
        ];

        foreach ($eventTypes as $type) {
            if ($period === 'hourly') {
                $key = self::CACHE_PREFIX . 'hourly:' . $type . ':' . now()->format('Y-m-d-H');
            } else {
                $key = self::CACHE_PREFIX . 'daily:' . $type . ':' . now()->format('Y-m-d');
            }
            
            $stats[$type] = Cache::get($key, 0);
        }

        return $stats;
    }

    /**
     * Get security trends over time.
     */
    public function getTrends(int $hours = 24): array
    {
        $trends = [];
        $eventTypes = ['failed_login', 'rate_limit_exceeded', 'suspicious_activity'];

        for ($i = $hours; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $hourData = ['hour' => $hour];

            foreach ($eventTypes as $type) {
                $key = self::CACHE_PREFIX . 'hourly:' . $type . ':' . $hour;
                $hourData[$type] = Cache::get($key, 0);
            }

            $trends[] = $hourData;
        }

        return $trends;
    }

    /**
     * Check for suspicious patterns.
     */
    public function detectSuspiciousPatterns(): array
    {
        $suspicious = [];
        
        // Check for multiple failed logins from same IP
        $this->checkFailedLoginPatterns($suspicious);
        
        // Check for rapid-fire requests
        $this->checkRapidFireRequests($suspicious);
        
        // Check for unusual user agent patterns
        $this->checkUserAgentPatterns($suspicious);
        
        // Check for spam patterns
        $this->checkSpamPatterns($suspicious);
        
        return $suspicious;
    }

    /**
     * Check for failed login patterns.
     */
    private function checkFailedLoginPatterns(array &$suspicious): void
    {
        $ip = request()->ip();
        $key = self::CACHE_PREFIX . 'failed_logins:' . $ip . ':' . now()->format('Y-m-d-H');
        $count = Cache::get($key, 0);
        
        if ($count >= 5) {
            $suspicious[] = [
                'type' => 'multiple_failed_logins',
                'ip' => $ip,
                'count' => $count,
                'severity' => $count >= 10 ? 'high' : 'medium',
            ];
        }
    }

    /**
     * Check for rapid-fire requests.
     */
    private function checkRapidFireRequests(array &$suspicious): void
    {
        $ip = request()->ip();
        $key = self::CACHE_PREFIX . 'requests:' . $ip . ':' . now()->format('Y-m-d-H-i');
        $count = Cache::get($key, 0);
        
        if ($count >= 100) { // More than 100 requests per minute
            $suspicious[] = [
                'type' => 'rapid_fire_requests',
                'ip' => $ip,
                'count' => $count,
                'severity' => 'high',
            ];
        }
    }

    /**
     * Check for unusual user agent patterns.
     */
    private function checkUserAgentPatterns(array &$suspicious): void
    {
        $userAgent = request()->userAgent();
        
        // Check for bot-like user agents
        $botPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
        ];
        
        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $suspicious[] = [
                    'type' => 'bot_user_agent',
                    'user_agent' => $userAgent,
                    'severity' => 'low',
                ];
                break;
            }
        }
        
        // Check for empty or suspicious user agents
        if (empty($userAgent) || strlen($userAgent) < 10) {
            $suspicious[] = [
                'type' => 'suspicious_user_agent',
                'user_agent' => $userAgent,
                'severity' => 'medium',
            ];
        }
    }

    /**
     * Check for spam patterns.
     */
    private function checkSpamPatterns(array &$suspicious): void
    {
        $ip = request()->ip();
        $userId = auth()->id();
        
        // Check for rapid message sending
        if ($userId) {
            $key = self::CACHE_PREFIX . 'messages:' . $userId . ':' . now()->format('Y-m-d-H');
            $count = Cache::get($key, 0);
            
            if ($count >= 50) { // More than 50 messages per hour
                $suspicious[] = [
                    'type' => 'rapid_messaging',
                    'user_id' => $userId,
                    'count' => $count,
                    'severity' => 'medium',
                ];
            }
        }
    }

    /**
     * Block IP address temporarily.
     */
    public function blockIp(string $ip, int $minutes = 60, string $reason = 'Security violation'): void
    {
        $key = self::CACHE_PREFIX . 'blocked_ip:' . $ip;
        Cache::put($key, [
            'blocked_at' => now()->toISOString(),
            'reason' => $reason,
            'expires_at' => now()->addMinutes($minutes)->toISOString(),
        ], $minutes * 60);

        $this->recordEvent('ip_blocked', [
            'ip' => $ip,
            'reason' => $reason,
            'duration_minutes' => $minutes,
        ]);
    }

    /**
     * Check if IP is blocked.
     */
    public function isIpBlocked(string $ip): bool
    {
        $key = self::CACHE_PREFIX . 'blocked_ip:' . $ip;
        return Cache::has($key);
    }

    /**
     * Get blocked IP information.
     */
    public function getBlockedIpInfo(string $ip): ?array
    {
        $key = self::CACHE_PREFIX . 'blocked_ip:' . $ip;
        return Cache::get($key);
    }

    /**
     * Unblock IP address.
     */
    public function unblockIp(string $ip): void
    {
        $key = self::CACHE_PREFIX . 'blocked_ip:' . $ip;
        Cache::forget($key);

        $this->recordEvent('ip_unblocked', ['ip' => $ip]);
    }

    /**
     * Clean up old monitoring data.
     */
    public function cleanup(): void
    {
        // This would typically be run as a scheduled job
        // to clean up old cache entries and logs
        
        Log::info('Security monitoring cleanup completed');
    }
}