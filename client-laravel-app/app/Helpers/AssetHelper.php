<?php

namespace App\Helpers;

class AssetHelper
{
    /**
     * Get the main AfricaVLP logo
     */
    public static function logo(): string
    {
        return asset('img/logo.png');
    }

    /**
     * Get the colored AfricaVLP logo
     */
    public static function logoColor(): string
    {
        return asset('img/logo-color.png');
    }

    /**
     * Get the footer AfricaVLP logo
     */
    public static function logoFooter(): string
    {
        return asset('img/logo-footer.png');
    }

    /**
     * Get default user avatar
     */
    public static function defaultAvatar(): string
    {
        return asset('img/user.png');
    }

    /**
     * Get alternative user avatar
     */
    public static function alternativeAvatar(): string
    {
        return asset('img/user2.png');
    }

    /**
     * Get placeholder for missing logos
     */
    public static function noLogo(): string
    {
        return asset('img/no-logo.jpg');
    }

    /**
     * Get placeholder for missing images
     */
    public static function noImage(): string
    {
        return asset('img/no-image.jpg');
    }

    /**
     * Get organization icon
     */
    public static function orgIcon(): string
    {
        return asset('img/org-icon.svg');
    }

    /**
     * Get user icon
     */
    public static function userIcon(): string
    {
        return asset('img/user-icon.svg');
    }

    /**
     * Get Africa map image
     */
    public static function africaMap(): string
    {
        return asset('img/africa_map.png');
    }

    /**
     * Get login background image
     */
    public static function loginBackground(): string
    {
        return asset('img/login-img.jpg');
    }

    /**
     * Get dashboard image
     */
    public static function dashboardImage(): string
    {
        return asset('img/dash-img.png');
    }

    /**
     * Get email confirmation image
     */
    public static function emailConfirm(): string
    {
        return asset('img/email-confirm.png');
    }

    /**
     * Get flag image
     */
    public static function flag(): string
    {
        return asset('img/flag.png');
    }

    /**
     * Get language selector image
     */
    public static function languageImage(): string
    {
        return asset('img/language-img.png');
    }

    /**
     * Get PDF icon
     */
    public static function pdfIcon(): string
    {
        return asset('img/pdf.png');
    }

    /**
     * Get about page images
     */
    public static function aboutImage(int $number = 1): string
    {
        return asset("img/about-img-0{$number}.jpg");
    }

    /**
     * Get social media icons
     */
    public static function socialIcon(string $platform): string
    {
        $validPlatforms = ['facebook', 'twitter', 'youtube', 'inst', 'web', 'rss'];
        
        if (in_array($platform, $validPlatforms)) {
            return asset("img/{$platform}.svg");
        }
        
        return asset('img/web.svg'); // Default fallback
    }

    /**
     * Get action icons
     */
    public static function actionIcon(string $action): string
    {
        $validActions = [
            'add' => 'add-icon.svg',
            'edit' => 'edit-icon.svg',
            'close' => 'close.svg',
            'close-dark' => 'close-dark.svg',
            'export' => 'export.svg',
            'filter' => 'filter.svg',
            'date' => 'date.svg',
            'email' => 'email.svg',
            'email-sent' => 'email-sent.svg',
            'address' => 'address.svg',
            'created' => 'created.svg',
            'upload' => 'upload.svg',
            'upload-file' => 'upload-file.svg',
            'upload-logo' => 'upload-logo.svg',
            'profile-upload' => 'profile-upload.svg',
            'table-menu' => 'table-menu.svg',
            'send' => 'send.svg',
            'badge' => 'badge.svg',
        ];
        
        if (isset($validActions[$action])) {
            return asset("img/{$validActions[$action]}");
        }
        
        return asset('img/add-icon.svg'); // Default fallback
    }

    /**
     * Get category icons for content types
     */
    public static function categoryIcon(string $category): string
    {
        $categoryIcons = [
            'volunteer-opportunities' => 'Volunteer-Opportunities.svg',
            'good-practices' => 'Good-Practices.svg',
            'impact-volunteerism' => 'Impact-of-Volunteerism.svg',
            'country-profile' => 'Country-profile.svg',
            'policy' => 'Policy.svg',
            'about' => 'about-icon.svg',
        ];
        
        if (isset($categoryIcons[$category])) {
            return asset("img/{$categoryIcons[$category]}");
        }
        
        return asset('img/about-icon.svg'); // Default fallback
    }

    /**
     * Get all available assets for verification
     */
    public static function getAllAssets(): array
    {
        return [
            'logos' => [
                'main' => self::logo(),
                'color' => self::logoColor(),
                'footer' => self::logoFooter(),
                'no_logo' => self::noLogo(),
            ],
            'users' => [
                'default_avatar' => self::defaultAvatar(),
                'alternative_avatar' => self::alternativeAvatar(),
                'user_icon' => self::userIcon(),
            ],
            'organizations' => [
                'org_icon' => self::orgIcon(),
                'org_user' => asset('img/org-user.png'),
            ],
            'backgrounds' => [
                'login' => self::loginBackground(),
                'dashboard' => self::dashboardImage(),
                'africa_map' => self::africaMap(),
                'slider' => asset('img/slider-1.jpg'),
                'programs' => asset('img/programes.jpg'),
                'big_page' => asset('img/big-pg.jpg'),
            ],
            'content' => [
                'no_image' => self::noImage(),
                'email_confirm' => self::emailConfirm(),
                'flag' => self::flag(),
                'language' => self::languageImage(),
                'pdf' => self::pdfIcon(),
                'text_bg' => asset('img/text-bg.png'),
            ],
            'about_images' => [
                'about_1' => self::aboutImage(1),
                'about_2' => self::aboutImage(2),
                'about_3' => self::aboutImage(3),
                'about_main' => asset('img/about.jpg'),
            ],
        ];
    }

    /**
     * Verify all critical assets exist
     */
    public static function verifyAssets(): array
    {
        $criticalAssets = [
            'logo.png',
            'logo-color.png',
            'logo-footer.png',
            'user.png',
            'no-logo.jpg',
            'no-image.jpg',
            'africa_map.png',
            'login-img.jpg',
        ];

        $missing = [];
        $existing = [];

        foreach ($criticalAssets as $asset) {
            $path = public_path("img/{$asset}");
            if (file_exists($path)) {
                $existing[] = $asset;
            } else {
                $missing[] = $asset;
            }
        }

        return [
            'existing' => $existing,
            'missing' => $missing,
            'total_critical' => count($criticalAssets),
            'verification_passed' => empty($missing),
        ];
    }
}
