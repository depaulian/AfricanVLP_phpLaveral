<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Forum Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the forum system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Attachment Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for file attachments in forum posts.
    |
    */
    'attachments' => [
        /*
        |--------------------------------------------------------------------------
        | Maximum File Size
        |--------------------------------------------------------------------------
        |
        | Maximum file size for attachments in bytes (default: 10MB)
        |
        */
        'max_file_size' => env('FORUM_MAX_FILE_SIZE', 10 * 1024 * 1024),

        /*
        |--------------------------------------------------------------------------
        | Maximum Attachments Per Post
        |--------------------------------------------------------------------------
        |
        | Maximum number of attachments allowed per forum post
        |
        */
        'max_attachments_per_post' => env('FORUM_MAX_ATTACHMENTS_PER_POST', 5),

        /*
        |--------------------------------------------------------------------------
        | Allowed MIME Types
        |--------------------------------------------------------------------------
        |
        | List of allowed MIME types for forum attachments
        |
        */
        'allowed_mime_types' => [
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            
            // Archives
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            
            // Code files
            'text/html',
            'text/css',
            'text/javascript',
            'application/json',
            'application/xml',
        ],

        /*
        |--------------------------------------------------------------------------
        | Dangerous File Extensions
        |--------------------------------------------------------------------------
        |
        | File extensions that are not allowed for security reasons
        |
        */
        'dangerous_extensions' => [
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
            'php', 'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh'
        ],

        /*
        |--------------------------------------------------------------------------
        | Storage Disk
        |--------------------------------------------------------------------------
        |
        | The disk where forum attachments will be stored
        |
        */
        'storage_disk' => env('FORUM_STORAGE_DISK', 'private'),

        /*
        |--------------------------------------------------------------------------
        | Storage Path
        |--------------------------------------------------------------------------
        |
        | The base path where forum attachments will be stored
        |
        */
        'storage_path' => env('FORUM_STORAGE_PATH', 'forum-attachments'),

        /*
        |--------------------------------------------------------------------------
        | Virus Scanning
        |--------------------------------------------------------------------------
        |
        | Enable virus scanning for uploaded files (requires ClamAV)
        |
        */
        'virus_scanning' => env('FORUM_VIRUS_SCANNING', false),

        /*
        |--------------------------------------------------------------------------
        | Image Processing
        |--------------------------------------------------------------------------
        |
        | Configuration for image processing
        |
        */
        'image_processing' => [
            'enabled' => env('FORUM_IMAGE_PROCESSING', true),
            'max_width' => env('FORUM_IMAGE_MAX_WIDTH', 1920),
            'max_height' => env('FORUM_IMAGE_MAX_HEIGHT', 1080),
            'quality' => env('FORUM_IMAGE_QUALITY', 85),
            'create_thumbnails' => env('FORUM_CREATE_THUMBNAILS', true),
            'thumbnail_width' => env('FORUM_THUMBNAIL_WIDTH', 300),
            'thumbnail_height' => env('FORUM_THUMBNAIL_HEIGHT', 300),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Forum Settings
    |--------------------------------------------------------------------------
    |
    | General forum configuration options
    |
    */
    'settings' => [
        /*
        |--------------------------------------------------------------------------
        | Posts Per Page
        |--------------------------------------------------------------------------
        |
        | Number of posts to display per page in forum threads
        |
        */
        'posts_per_page' => env('FORUM_POSTS_PER_PAGE', 10),

        /*
        |--------------------------------------------------------------------------
        | Threads Per Page
        |--------------------------------------------------------------------------
        |
        | Number of threads to display per page in forum listings
        |
        */
        'threads_per_page' => env('FORUM_THREADS_PER_PAGE', 20),

        /*
        |--------------------------------------------------------------------------
        | Search Results Per Page
        |--------------------------------------------------------------------------
        |
        | Number of search results to display per page
        |
        */
        'search_results_per_page' => env('FORUM_SEARCH_RESULTS_PER_PAGE', 15),

        /*
        |--------------------------------------------------------------------------
        | Edit Time Limit
        |--------------------------------------------------------------------------
        |
        | Time limit (in minutes) for editing posts and threads
        |
        */
        'edit_time_limit' => env('FORUM_EDIT_TIME_LIMIT', 1440), // 24 hours

        /*
        |--------------------------------------------------------------------------
        | Auto-save Interval
        |--------------------------------------------------------------------------
        |
        | Interval (in seconds) for auto-saving drafts
        |
        */
        'auto_save_interval' => env('FORUM_AUTO_SAVE_INTERVAL', 30),

        /*
        |--------------------------------------------------------------------------
        | Content Moderation
        |--------------------------------------------------------------------------
        |
        | Enable content moderation features
        |
        */
        'content_moderation' => env('FORUM_CONTENT_MODERATION', true),

        /*
        |--------------------------------------------------------------------------
        | Voting System
        |--------------------------------------------------------------------------
        |
        | Enable voting system for posts
        |
        */
        'voting_enabled' => env('FORUM_VOTING_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Solution Marking
        |--------------------------------------------------------------------------
        |
        | Enable solution marking for threads
        |
        */
        'solution_marking_enabled' => env('FORUM_SOLUTION_MARKING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for forum notifications
    |
    */
    'notifications' => [
        /*
        |--------------------------------------------------------------------------
        | Email Notifications
        |--------------------------------------------------------------------------
        |
        | Enable email notifications for forum activities
        |
        */
        'email_enabled' => env('FORUM_EMAIL_NOTIFICATIONS', true),

        /*
        |--------------------------------------------------------------------------
        | Real-time Notifications
        |--------------------------------------------------------------------------
        |
        | Enable real-time notifications using WebSockets
        |
        */
        'realtime_enabled' => env('FORUM_REALTIME_NOTIFICATIONS', false),

        /*
        |--------------------------------------------------------------------------
        | Digest Notifications
        |--------------------------------------------------------------------------
        |
        | Enable digest email notifications
        |
        */
        'digest_enabled' => env('FORUM_DIGEST_NOTIFICATIONS', true),
        'digest_frequency' => env('FORUM_DIGEST_FREQUENCY', 'weekly'), // daily, weekly, monthly
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for forum caching
    |
    */
    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Cache TTL
        |--------------------------------------------------------------------------
        |
        | Time to live for cached forum data (in minutes)
        |
        */
        'ttl' => env('FORUM_CACHE_TTL', 60),

        /*
        |--------------------------------------------------------------------------
        | Cache Tags
        |--------------------------------------------------------------------------
        |
        | Cache tags for forum data
        |
        */
        'tags' => [
            'forums' => 'forum_data',
            'threads' => 'forum_threads',
            'posts' => 'forum_posts',
            'attachments' => 'forum_attachments',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security configuration for forums
    |
    */
    'security' => [
        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        |
        | Rate limiting for forum actions
        |
        */
        'rate_limiting' => [
            'post_creation' => env('FORUM_POST_RATE_LIMIT', '10,1'), // 10 posts per minute
            'thread_creation' => env('FORUM_THREAD_RATE_LIMIT', '5,1'), // 5 threads per minute
            'voting' => env('FORUM_VOTE_RATE_LIMIT', '60,1'), // 60 votes per minute
        ],

        /*
        |--------------------------------------------------------------------------
        | Content Filtering
        |--------------------------------------------------------------------------
        |
        | Enable content filtering and spam detection
        |
        */
        'content_filtering' => env('FORUM_CONTENT_FILTERING', true),
        'spam_detection' => env('FORUM_SPAM_DETECTION', true),

        /*
        |--------------------------------------------------------------------------
        | IP Blocking
        |--------------------------------------------------------------------------
        |
        | Enable IP blocking for banned users
        |
        */
        'ip_blocking' => env('FORUM_IP_BLOCKING', false),
    ],
];