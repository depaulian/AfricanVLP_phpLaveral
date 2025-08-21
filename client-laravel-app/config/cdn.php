<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Content Delivery Network integration
    |
    */

    'enabled' => env('CDN_ENABLED', false),

    'default' => env('CDN_DEFAULT', 'cloudinary'),

    'providers' => [
        'cloudinary' => [
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
            'secure' => true,
            'base_url' => env('CLOUDINARY_BASE_URL', 'https://res.cloudinary.com'),
        ],

        'aws_s3' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        'local' => [
            'base_url' => env('APP_URL', 'http://localhost'),
            'path' => '/storage',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Types Configuration
    |--------------------------------------------------------------------------
    |
    | Define which asset types should be served from CDN
    |
    */

    'asset_types' => [
        'images' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'transformations' => [
                'thumbnail' => ['width' => 300, 'height' => 200, 'crop' => 'fill'],
                'medium' => ['width' => 600, 'height' => 400, 'crop' => 'fill'],
                'large' => ['width' => 1200, 'height' => 800, 'crop' => 'fill'],
            ],
        ],

        'documents' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
            'max_size' => 50 * 1024 * 1024, // 50MB
        ],

        'videos' => [
            'extensions' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
            'max_size' => 100 * 1024 * 1024, // 100MB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | CDN caching settings
    |
    */

    'cache' => [
        'ttl' => env('CDN_CACHE_TTL', 86400), // 24 hours
        'headers' => [
            'Cache-Control' => 'public, max-age=86400',
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 86400),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimization Settings
    |--------------------------------------------------------------------------
    |
    | Automatic optimization settings for assets
    |
    */

    'optimization' => [
        'auto_format' => true, // Automatically choose best format (WebP, AVIF)
        'auto_quality' => true, // Automatically optimize quality
        'progressive_jpeg' => true,
        'strip_metadata' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Volunteering Specific Settings
    |--------------------------------------------------------------------------
    |
    | CDN settings specific to volunteering system
    |
    */

    'volunteering' => [
        'opportunity_images' => [
            'folder' => 'volunteering/opportunities',
            'transformations' => [
                'card' => ['width' => 400, 'height' => 250, 'crop' => 'fill', 'quality' => 'auto'],
                'hero' => ['width' => 1200, 'height' => 600, 'crop' => 'fill', 'quality' => 'auto'],
                'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => 'fill', 'quality' => 'auto'],
            ],
        ],

        'organization_logos' => [
            'folder' => 'volunteering/organizations',
            'transformations' => [
                'small' => ['width' => 100, 'height' => 100, 'crop' => 'fit'],
                'medium' => ['width' => 200, 'height' => 200, 'crop' => 'fit'],
                'large' => ['width' => 400, 'height' => 400, 'crop' => 'fit'],
            ],
        ],

        'certificates' => [
            'folder' => 'volunteering/certificates',
            'format' => 'pdf',
        ],

        'documents' => [
            'folder' => 'volunteering/documents',
            'allowed_types' => ['pdf', 'doc', 'docx'],
        ],
    ],
];