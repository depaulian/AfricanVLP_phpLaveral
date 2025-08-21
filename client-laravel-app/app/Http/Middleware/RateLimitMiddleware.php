<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limiter = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        $limits = $this->getLimits($limiter);
        
        if (RateLimiter::tooManyAttempts($key, $limits['maxAttempts'])) {
            $retryAfter = RateLimiter::availableIn($key);
            
            // Log the rate limit violation
            logger()->warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'limiter' => $limiter,
                'retry_after' => $retryAfter
            ]);
            
            throw new ThrottleRequestsException('Too Many Attempts.', null, [], $retryAfter);
        }

        RateLimiter::hit($key, $limits['decayMinutes'] * 60);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $limits['maxAttempts'],
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $limits['maxAttempts']),
            'X-RateLimit-Reset' => now()->addMinutes($limits['decayMinutes'])->timestamp,
        ]);

        return $response;
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sha1('user:' . $user->id);
        }

        return sha1($request->ip() . '|' . $request->userAgent());
    }

    /**
     * Get rate limit configuration for the specified limiter.
     */
    protected function getLimits(string $limiter): array
    {
        $limits = [
            'default' => ['maxAttempts' => 60, 'decayMinutes' => 1],
            'auth' => ['maxAttempts' => 5, 'decayMinutes' => 15],
            'api' => ['maxAttempts' => 100, 'decayMinutes' => 1],
            'upload' => ['maxAttempts' => 10, 'decayMinutes' => 1],
            'password-reset' => ['maxAttempts' => 3, 'decayMinutes' => 60],
            'registration' => ['maxAttempts' => 3, 'decayMinutes' => 60],
            'contact' => ['maxAttempts' => 5, 'decayMinutes' => 60],
            'search' => ['maxAttempts' => 30, 'decayMinutes' => 1],
            'messaging' => ['maxAttempts' => 20, 'decayMinutes' => 1],
        ];

        return $limits[$limiter] ?? $limits['default'];
    }
}