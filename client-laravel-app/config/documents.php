<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Management Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the document management
    | system including file upload limits, allowed types, and security settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */
    'max_file_size' => env('DOCUMENT_MAX_FILE_SIZE', 10 * 1024 * 1024), // 10MB in bytes
    'max_files_per_user' => env('DOCUMENT_MAX_FILES_PER_USER', 50),
    'max_files_per_category' => env('DOCUMENT_MAX_FILES_PER_CATEGORY', 10),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    */
    'allowed_extensions' => [
        'pdf', 'doc', 'docx', 'txt', 'rtf',
        'jpg', 'jpeg', 'png', 'gif', 'bmp',
        'xls', 'xlsx', 'csv',
        'ppt', 'pptx',
        'zip', 'rar'
    ],

    'allowed_mime_types' => [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'application/rtf',
        
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        
        // Spreadsheets
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        
        // Presentations
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        
        // Archives
        'application/zip',
        'application/x-rar-compressed'
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Categories
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'identity' => [
            'name' => 'Identity Documents',
            'description' => 'Government-issued identification documents',
            'required_verification' => true,
            'max_files' => 3,
            'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png']
        ],
        'education' => [
            'name' => 'Educational Certificates',
            'description' => 'Diplomas, degrees, and educational certificates',
            'required_verification' => true,
            'max_files' => 10,
            'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png']
        ],
        'professional' => [
            'name' => 'Professional Certificates',
            'description' => 'Professional licenses and certifications',
            'required_verification' => true,
            'max_files' => 10,
            'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png']
        ],
        'resume' => [
            'name' => 'Resume/CV',
            'description' => 'Curriculum vitae and resume documents',
            'required_verification' => false,
            'max_files' => 3,
            'allowed_types' => ['pdf', 'doc', 'docx']
        ],
        'portfolio' => [
            'name' => 'Portfolio',
            'description' => 'Work samples and portfolio items',
            'required_verification' => false,
            'max_files' => 20,
            'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'ppt', 'pptx']
        ],
        'references' => [
            'name' => 'References',
            'description' => 'Reference letters and recommendations',
            'required_verification' => false,
            'max_files' => 5,
            'allowed_types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']
        ],
        'other' => [
            'name' => 'Other Documents',
            'description' => 'Miscellaneous documents',
            'required_verification' => false,
            'max_files' => 10,
            'allowed_types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt']
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'virus_scanning' => [
        'enabled' => env('DOCUMENT_VIRUS_SCAN_ENABLED', true),
        'engine' => env('DOCUMENT_VIRUS_SCAN_ENGINE', 'clamav'), // clamav, basic
        'quarantine_infected' => env('DOCUMENT_QUARANTINE_INFECTED', true),
        'notify_admin_on_threat' => env('DOCUMENT_NOTIFY_ADMIN_ON_THREAT', true)
    ],

    'encryption' => [
        'enabled' => env('DOCUMENT_ENCRYPTION_ENABLED', false),
        'algorithm' => env('DOCUMENT_ENCRYPTION_ALGORITHM', 'AES-256-CBC'),
        'key_rotation_days' => env('DOCUMENT_KEY_ROTATION_DAYS', 90)
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => env('DOCUMENT_STORAGE_DISK', 'private'),
        'backup_disk' => env('DOCUMENT_BACKUP_DISK', 'backup'),
        'cdn_enabled' => env('DOCUMENT_CDN_ENABLED', false),
        'cdn_url' => env('DOCUMENT_CDN_URL'),
        'compression_enabled' => env('DOCUMENT_COMPRESSION_ENABLED', true),
        'thumbnail_generation' => env('DOCUMENT_THUMBNAIL_GENERATION', true)
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Settings
    |--------------------------------------------------------------------------
    */
    'verification' => [
        'auto_verify_categories' => ['resume', 'portfolio', 'references', 'other'],
        'require_admin_verification' => ['identity', 'education', 'professional'],
        'verification_timeout_days' => env('DOCUMENT_VERIFICATION_TIMEOUT_DAYS', 7),
        'max_verification_attempts' => env('DOCUMENT_MAX_VERIFICATION_ATTEMPTS', 3),
        'notify_user_on_verification' => true,
        'notify_admin_on_submission' => true
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiration Settings
    |--------------------------------------------------------------------------
    */
    'expiration' => [
        'reminder_days' => [30, 14, 7, 1], // Days before expiry to send reminders
        'auto_archive_expired_days' => env('DOCUMENT_AUTO_ARCHIVE_EXPIRED_DAYS', 30),
        'auto_delete_expired_days' => env('DOCUMENT_AUTO_DELETE_EXPIRED_DAYS', 2555), // 7 years
        'default_expiry_years' => [
            'identity' => 10,
            'education' => null, // Never expires
            'professional' => 3,
            'resume' => 2,
            'portfolio' => null,
            'references' => 5,
            'other' => null
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Sharing Settings
    |--------------------------------------------------------------------------
    */
    'sharing' => [
        'enabled' => env('DOCUMENT_SHARING_ENABLED', true),
        'max_recipients' => env('DOCUMENT_MAX_SHARE_RECIPIENTS', 10),
        'default_expiry_days' => env('DOCUMENT_SHARE_DEFAULT_EXPIRY_DAYS', 7),
        'max_expiry_days' => env('DOCUMENT_SHARE_MAX_EXPIRY_DAYS', 30),
        'require_authentication' => env('DOCUMENT_SHARE_REQUIRE_AUTH', false),
        'track_access' => env('DOCUMENT_SHARE_TRACK_ACCESS', true),
        'watermark_shared_documents' => env('DOCUMENT_WATERMARK_SHARED', false)
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('DOCUMENT_BACKUP_ENABLED', true),
        'frequency' => env('DOCUMENT_BACKUP_FREQUENCY', 'daily'), // daily, weekly, monthly
        'retention_days' => env('DOCUMENT_BACKUP_RETENTION_DAYS', 90),
        'compress_backups' => env('DOCUMENT_BACKUP_COMPRESS', true),
        'verify_backups' => env('DOCUMENT_BACKUP_VERIFY', true),
        'offsite_backup' => env('DOCUMENT_OFFSITE_BACKUP', false)
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Settings
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'enabled' => env('DOCUMENT_ANALYTICS_ENABLED', true),
        'track_downloads' => env('DOCUMENT_TRACK_DOWNLOADS', true),
        'track_views' => env('DOCUMENT_TRACK_VIEWS', true),
        'track_shares' => env('DOCUMENT_TRACK_SHARES', true),
        'retention_days' => env('DOCUMENT_ANALYTICS_RETENTION_DAYS', 365)
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'cache_enabled' => env('DOCUMENT_CACHE_ENABLED', true),
        'cache_ttl' => env('DOCUMENT_CACHE_TTL', 3600), // 1 hour
        'lazy_loading' => env('DOCUMENT_LAZY_LOADING', true),
        'thumbnail_cache_ttl' => env('DOCUMENT_THUMBNAIL_CACHE_TTL', 86400), // 24 hours
        'metadata_cache_ttl' => env('DOCUMENT_METADATA_CACHE_TTL', 1800) // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'upload_confirmation' => env('DOCUMENT_NOTIFY_UPLOAD', true),
        'verification_status' => env('DOCUMENT_NOTIFY_VERIFICATION', true),
        'expiration_reminders' => env('DOCUMENT_NOTIFY_EXPIRATION', true),
        'share_notifications' => env('DOCUMENT_NOTIFY_SHARING', true),
        'admin_notifications' => env('DOCUMENT_NOTIFY_ADMIN', true),
        'digest_frequency' => env('DOCUMENT_DIGEST_FREQUENCY', 'weekly') // daily, weekly, monthly
    ]
];