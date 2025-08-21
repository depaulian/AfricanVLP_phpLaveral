<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class MobileDetectionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMobile = $this->isMobileDevice($request);
        $isTablet = $this->isTabletDevice($request);
        
        // Share mobile detection with all views
        View::share('isMobile', $isMobile);
        View::share('isTablet', $isTablet);
        View::share('isMobileOrTablet', $isMobile || $isTablet);
        
        // Add mobile detection to request
        $request->attributes->set('is_mobile', $isMobile);
        $request->attributes->set('is_tablet', $isTablet);
        
        // Set mobile-specific headers
        if ($isMobile || $isTablet) {
            $response = $next($request);
            
            // Add mobile-optimized headers
            if (method_exists($response, 'header')) {
                $response->header('X-Mobile-Optimized', 'true');
                $response->header('Viewport', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
            }
            
            return $response;
        }
        
        return $next($request);
    }

    /**
     * Detect if the request is from a mobile device
     */
    private function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Mobile device patterns
        $mobilePatterns = [
            '/Mobile/i',
            '/Android/i',
            '/iPhone/i',
            '/iPod/i',
            '/BlackBerry/i',
            '/Windows Phone/i',
            '/webOS/i',
            '/Opera Mini/i',
            '/IEMobile/i',
            '/Mobile Safari/i',
            '/Nokia/i',
            '/Samsung/i',
            '/LG/i',
            '/HTC/i',
            '/Motorola/i'
        ];
        
        foreach ($mobilePatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        // Check for mobile-specific headers
        $mobileHeaders = [
            'HTTP_X_WAP_PROFILE',
            'HTTP_X_WAP_CLIENTID',
            'HTTP_WAP_CONNECTION',
            'HTTP_PROFILE',
            'HTTP_X_OPERAMINI_PHONE_UA',
            'HTTP_X_NOKIA_GATEWAY_ID',
            'HTTP_X_ORANGE_ID',
            'HTTP_X_VODAFONE_3GPDPCONTEXT',
            'HTTP_X_HUAWEI_USERID'
        ];
        
        foreach ($mobileHeaders as $header) {
            if ($request->header($header)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Detect if the request is from a tablet device
     */
    private function isTabletDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Tablet device patterns
        $tabletPatterns = [
            '/iPad/i',
            '/Android.*Tablet/i',
            '/Android.*Tab/i',
            '/Kindle/i',
            '/PlayBook/i',
            '/Nexus 7/i',
            '/Nexus 10/i',
            '/Galaxy Tab/i',
            '/Xoom/i',
            '/sch-i800/i',
            '/tablet/i'
        ];
        
        foreach ($tabletPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get device type string
     */
    public static function getDeviceType(Request $request): string
    {
        $middleware = new self();
        
        if ($middleware->isTabletDevice($request)) {
            return 'tablet';
        }
        
        if ($middleware->isMobileDevice($request)) {
            return 'mobile';
        }
        
        return 'desktop';
    }

    /**
     * Check if device supports touch
     */
    public static function isTouchDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        
        return preg_match('/(Touch|Mobile|Android|iPhone|iPad)/i', $userAgent) ||
               $request->header('HTTP_X_TOUCH_CAPABLE') === 'true';
    }

    /**
     * Get screen size category based on user agent
     */
    public static function getScreenSize(Request $request): string
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Large tablets
        if (preg_match('/(iPad|Nexus 10|Galaxy Tab.*10)/i', $userAgent)) {
            return 'large';
        }
        
        // Small tablets
        if (preg_match('/(Tablet|Nexus 7|Kindle)/i', $userAgent)) {
            return 'medium';
        }
        
        // Mobile phones
        if (preg_match('/(Mobile|iPhone|Android.*Mobile)/i', $userAgent)) {
            return 'small';
        }
        
        return 'desktop';
    }

    /**
     * Check if device has camera capabilities
     */
    public static function hasCameraSupport(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Most modern mobile devices have cameras
        return preg_match('/(iPhone|iPad|Android|Mobile)/i', $userAgent) &&
               !preg_match('/(Opera Mini|UC Browser)/i', $userAgent);
    }

    /**
     * Check if device supports offline storage
     */
    public static function supportsOfflineStorage(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Modern browsers support service workers and local storage
        return !preg_match('/(Opera Mini|UC Browser|Internet Explorer [1-9])/i', $userAgent);
    }

    /**
     * Get recommended image quality based on device
     */
    public static function getRecommendedImageQuality(Request $request): int
    {
        $deviceType = self::getDeviceType($request);
        
        return match($deviceType) {
            'mobile' => 70,  // Lower quality for mobile to save bandwidth
            'tablet' => 80,  // Medium quality for tablets
            default => 90    // High quality for desktop
        };
    }

    /**
     * Get maximum file upload size based on device
     */
    public static function getMaxUploadSize(Request $request): int
    {
        $deviceType = self::getDeviceType($request);
        
        return match($deviceType) {
            'mobile' => 5 * 1024 * 1024,   // 5MB for mobile
            'tablet' => 10 * 1024 * 1024,  // 10MB for tablet
            default => 25 * 1024 * 1024    // 25MB for desktop
        };
    }
}