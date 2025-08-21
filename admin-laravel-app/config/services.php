<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'secure' => true,
        'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
        'from_email' => env('SENDGRID_FROM_EMAIL'),
        'from_name' => env('SENDGRID_FROM_NAME', 'African Universities VLP'),
    ],

    'google_translate' => [
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
    ],

    'mobile_detect' => [
        'enabled' => env('MOBILE_DETECT_ENABLED', true),
        'cache_duration' => env('MOBILE_DETECT_CACHE_DURATION', 3600), // 1 hour
    ],

    'newsletter' => [
        'enabled' => env('NEWSLETTER_ENABLED', true),
        'provider' => env('NEWSLETTER_PROVIDER', 'sendgrid'), // sendgrid, mailchimp, etc.
        'list_id' => env('NEWSLETTER_LIST_ID'),
    ],

    'social_auth' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI'),
        ],
        'facebook' => [
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('FACEBOOK_REDIRECT_URI'),
        ],
    ],

    'analytics' => [
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),
        'facebook_pixel_id' => env('FACEBOOK_PIXEL_ID'),
    ],

    'maps' => [
        'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];