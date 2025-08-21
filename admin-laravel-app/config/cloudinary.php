<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Cloudinary image and file upload service
    |
    */

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'secure' => env('CLOUDINARY_SECURE', true),
    
    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    */
    
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    
    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    */
    
    'max_file_size' => env('CLOUDINARY_MAX_FILE_SIZE', 10485760), // 10MB default
    'allowed_formats' => [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        'audio' => ['mp3', 'wav', 'ogg', 'aac', 'flac']
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Transformation Settings
    |--------------------------------------------------------------------------
    */
    
    'transformations' => [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'crop' => 'fill',
            'quality' => 'auto'
        ],
        'medium' => [
            'width' => 500,
            'height' => 500,
            'crop' => 'limit',
            'quality' => 'auto'
        ],
        'large' => [
            'width' => 1200,
            'height' => 1200,
            'crop' => 'limit',
            'quality' => 'auto'
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Folder Structure
    |--------------------------------------------------------------------------
    */
    
    'folders' => [
        'resources' => 'au-vlp/resources',
        'profiles' => 'au-vlp/profiles',
        'organizations' => 'au-vlp/organizations',
        'events' => 'au-vlp/events',
        'news' => 'au-vlp/news',
        'blog' => 'au-vlp/blog'
    ]
];