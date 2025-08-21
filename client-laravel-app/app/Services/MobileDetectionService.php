<?php

namespace App\Services;

use Detection\MobileDetect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class MobileDetectionService
{
    protected $mobileDetect;
    protected $config;

    public function __construct()
    {
        $this->mobileDetect = new MobileDetect();
        $this->config = config('services.mobile_detect');
    }

    /**
     * Check if the current request is from a mobile device
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        $cacheKey = 'mobile_detect_' . md5(Request::header('User-Agent', ''));
        
        return Cache::remember($cacheKey, $this->config['cache_duration'], function () {
            return $this->mobileDetect->isMobile();
        });
    }

    /**
     * Check if the current request is from a tablet
     *
     * @return bool
     */
    public function isTablet(): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        $cacheKey = 'tablet_detect_' . md5(Request::header('User-Agent', ''));
        
        return Cache::remember($cacheKey, $this->config['cache_duration'], function () {
            return $this->mobileDetect->isTablet();
        });
    }

    /**
     * Check if the current request is from a desktop
     *
     * @return bool
     */
    public function isDesktop(): bool
    {
        return !$this->isMobile() && !$this->isTablet();
    }

    /**
     * Get device type
     *
     * @return string
     */
    public function getDeviceType(): string
    {
        if ($this->isTablet()) {
            return 'tablet';
        } elseif ($this->isMobile()) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }

    /**
     * Check if device is a specific type
     *
     * @param string $deviceName
     * @return bool
     */
    public function isDevice(string $deviceName): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        $cacheKey = 'device_detect_' . $deviceName . '_' . md5(Request::header('User-Agent', ''));
        
        return Cache::remember($cacheKey, $this->config['cache_duration'], function () use ($deviceName) {
            return $this->mobileDetect->is($deviceName);
        });
    }

    /**
     * Get operating system
     *
     * @return string|null
     */
    public function getOperatingSystem(): ?string
    {
        if (!$this->config['enabled']) {
            return null;
        }

        $cacheKey = 'os_detect_' . md5(Request::header('User-Agent', ''));
        
        return Cache::remember($cacheKey, $this->config['cache_duration'], function () {
            $operatingSystems = [
                'AndroidOS', 'BlackBerryOS', 'PalmOS', 'SymbianOS', 'WindowsMobileOS', 'WindowsPhoneOS',
                'iOS', 'MeeGoOS', 'MaemoOS', 'JavaOS', 'webOS', 'badaOS', 'BREWOS'
            ];

            foreach ($operatingSystems as $os) {
                if ($this->mobileDetect->is($os)) {
                    return $os;
                }
            }

            return null;
        });
    }

    /**
     * Get browser information
     *
     * @return array
     */
    public function getBrowserInfo(): array
    {
        if (!$this->config['enabled']) {
            return [
                'name' => 'Unknown',
                'version' => 'Unknown'
            ];
        }

        $cacheKey = 'browser_detect_' . md5(Request::header('User-Agent', ''));
        
        return Cache::remember($cacheKey, $this->config['cache_duration'], function () {
            $browsers = [
                'Chrome', 'Firefox', 'Safari', 'Opera', 'IE', 'Edge',
                'AndroidBrowser', 'UCBrowser', 'SamsungBrowser'
            ];

            foreach ($browsers as $browser) {
                if ($this->mobileDetect->is($browser)) {
                    return [
                        'name' => $browser,
                        'version' => $this->mobileDetect->version($browser) ?: 'Unknown'
                    ];
                }
            }

            return [
                'name' => 'Unknown',
                'version' => 'Unknown'
            ];
        });
    }

    /**
     * Get device information summary
     *
     * @return array
     */
    public function getDeviceInfo(): array
    {
        return [
            'device_type' => $this->getDeviceType(),
            'is_mobile' => $this->isMobile(),
            'is_tablet' => $this->isTablet(),
            'is_desktop' => $this->isDesktop(),
            'operating_system' => $this->getOperatingSystem(),
            'browser' => $this->getBrowserInfo(),
            'user_agent' => Request::header('User-Agent', ''),
            'detection_enabled' => $this->config['enabled']
        ];
    }

    /**
     * Get responsive breakpoint based on device
     *
     * @return string
     */
    public function getResponsiveBreakpoint(): string
    {
        if ($this->isMobile()) {
            return 'sm'; // Small screens
        } elseif ($this->isTablet()) {
            return 'md'; // Medium screens
        } else {
            return 'lg'; // Large screens
        }
    }

    /**
     * Get recommended image size based on device
     *
     * @return array
     */
    public function getRecommendedImageSize(): array
    {
        if ($this->isMobile()) {
            return [
                'width' => 480,
                'height' => 320,
                'quality' => 80
            ];
        } elseif ($this->isTablet()) {
            return [
                'width' => 768,
                'height' => 512,
                'quality' => 85
            ];
        } else {
            return [
                'width' => 1200,
                'height' => 800,
                'quality' => 90
            ];
        }
    }

    /**
     * Check if device supports specific features
     *
     * @param string $feature
     * @return bool
     */
    public function supportsFeature(string $feature): bool
    {
        $features = [
            'touch' => $this->isMobile() || $this->isTablet(),
            'geolocation' => true, // Most modern browsers support this
            'camera' => $this->isMobile() || $this->isTablet(),
            'push_notifications' => true, // Most modern browsers support this
            'offline' => true, // Service workers are widely supported
        ];

        return $features[$feature] ?? false;
    }

    /**
     * Get device-specific CSS classes
     *
     * @return array
     */
    public function getCssClasses(): array
    {
        $classes = [];

        if ($this->isMobile()) {
            $classes[] = 'is-mobile';
        }

        if ($this->isTablet()) {
            $classes[] = 'is-tablet';
        }

        if ($this->isDesktop()) {
            $classes[] = 'is-desktop';
        }

        $os = $this->getOperatingSystem();
        if ($os) {
            $classes[] = 'os-' . strtolower(str_replace('OS', '', $os));
        }

        $browser = $this->getBrowserInfo();
        if ($browser['name'] !== 'Unknown') {
            $classes[] = 'browser-' . strtolower($browser['name']);
        }

        return $classes;
    }

    /**
     * Clear detection cache
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        try {
            $patterns = ['mobile_detect_*', 'tablet_detect_*', 'device_detect_*', 'os_detect_*', 'browser_detect_*'];
            
            foreach ($patterns as $pattern) {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}