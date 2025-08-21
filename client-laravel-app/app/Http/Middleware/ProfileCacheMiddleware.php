<?php

namespace App\Http\Middleware;

use App\Services\ProfileCacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProfileCacheMiddleware
{
    protected ProfileCacheService $cacheService;

    public function __construct(ProfileCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $cacheType = 'complete'): Response
    {
        $response = $next($request);

        // Only process for authenticated users
        if (!Auth::check()) {
            return $response;
        }

        $userId = Auth::id();

        try {
            // Warm up cache if not exists
            if (!$this->cacheService->hasCachedData($userId, $cacheType)) {
                $this->warmUpCache($userId, $cacheType);
            }

            // Add cache headers
            $this->addCacheHeaders($response, $userId, $cacheType);

        } catch (\Exception $e) {
            Log::warning('Profile cache middleware failed', [
                'user_id' => $userId,
                'cache_type' => $cacheType,
                'error' => $e->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * Warm up specific cache type for user
     */
    protected function warmUpCache(int $userId, string $cacheType): void
    {
        switch ($cacheType) {
            case 'profile':
                $this->cacheService->getProfile($userId, true);
                break;
                
            case 'complete':
                $this->cacheService->getCompleteProfile($userId, true);
                break;
                
            case 'stats':
                $this->cacheService->getProfileStats($userId, true);
                break;
                
            case 'skills':
                $this->cacheService->getUserSkills($userId, true);
                break;
                
            case 'interests':
                $this->cacheService->getUserInterests($userId, true);
                break;
                
            case 'history':
                $this->cacheService->getVolunteeringHistory($userId, true);
                break;
                
            case 'documents':
                $this->cacheService->getUserDocuments($userId, true);
                break;
                
            default:
                $this->cacheService->warmUpUserCache($userId);
                break;
        }
    }

    /**
     * Add cache-related headers to response
     */
    protected function addCacheHeaders(Response $response, int $userId, string $cacheType): void
    {
        $expiration = $this->cacheService->getCacheExpiration($userId, $cacheType);
        
        if ($expiration) {
            $response->headers->set('X-Profile-Cache-Status', 'HIT');
            $response->headers->set('X-Profile-Cache-Expires', $expiration->toISOString());
            $response->headers->set('X-Profile-Cache-Type', $cacheType);
        } else {
            $response->headers->set('X-Profile-Cache-Status', 'MISS');
        }
    }
}