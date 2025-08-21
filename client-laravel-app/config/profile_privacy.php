<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Profile Privacy Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for user profile privacy settings.
    | These settings control what information is visible to different user types.
    |
    */

    'default_settings' => [
        'profile_visibility' => 'public', // public, organization, private
        'contact_info' => 'organization', // public, organization, private
        'skills' => 'public',
        'volunteering_history' => 'public',
        'volunteering_interests' => 'public',
        'documents' => 'private',
        'alumni_organizations' => 'public',
        'allow_messages' => true,
        'messages_from' => 'verified', // anyone, verified, organization
        'show_online_status' => true,
        'show_last_active' => false,
        'allow_profile_indexing' => true, // Allow search engines to index
        'show_in_directory' => true,
        'allow_data_export' => true,
    ],

    'privacy_levels' => [
        'public' => [
            'label' => 'Public',
            'description' => 'Visible to everyone, including non-registered users',
            'icon' => 'globe',
        ],
        'organization' => [
            'label' => 'Organization Members',
            'description' => 'Visible only to members of your organizations',
            'icon' => 'users',
        ],
        'private' => [
            'label' => 'Private',
            'description' => 'Visible only to you and administrators',
            'icon' => 'lock',
        ],
    ],

    'message_restrictions' => [
        'anyone' => [
            'label' => 'Anyone',
            'description' => 'Anyone can send you messages',
        ],
        'verified' => [
            'label' => 'Verified Users',
            'description' => 'Only verified users can send you messages',
        ],
        'organization' => [
            'label' => 'Organization Members',
            'description' => 'Only members of your organizations can send you messages',
        ],
    ],

    'profile_sections' => [
        'basic_info' => [
            'label' => 'Basic Information',
            'description' => 'Name, bio, location, and profile picture',
            'required' => true, // Cannot be made private
        ],
        'contact_info' => [
            'label' => 'Contact Information',
            'description' => 'Email, phone number, and social media links',
            'required' => false,
        ],
        'skills' => [
            'label' => 'Skills & Expertise',
            'description' => 'Your skills, proficiency levels, and experience',
            'required' => false,
        ],
        'volunteering_history' => [
            'label' => 'Volunteering History',
            'description' => 'Your past volunteering experiences and roles',
            'required' => false,
        ],
        'volunteering_interests' => [
            'label' => 'Volunteering Interests',
            'description' => 'Areas you are interested in volunteering',
            'required' => false,
        ],
        'documents' => [
            'label' => 'Documents',
            'description' => 'Uploaded certificates, resumes, and other documents',
            'required' => false,
        ],
        'alumni_organizations' => [
            'label' => 'Alumni Organizations',
            'description' => 'Educational institutions and organizations you are affiliated with',
            'required' => false,
        ],
    ],

    'admin_override' => [
        'roles' => ['admin', 'super_admin'],
        'can_view_all' => true,
        'can_modify_privacy' => true,
        'bypass_restrictions' => true,
    ],

    'verification_benefits' => [
        'increased_visibility' => true,
        'priority_in_search' => true,
        'trust_badge' => true,
        'access_to_premium_features' => true,
    ],

    'data_retention' => [
        'profile_deletion_grace_period' => 30, // days
        'inactive_profile_warning' => 365, // days
        'automatic_anonymization' => 1095, // days (3 years)
        'backup_retention' => 2555, // days (7 years)
    ],

    'security_settings' => [
        'require_email_verification' => true,
        'require_phone_verification' => false,
        'enable_two_factor' => false,
        'log_profile_access' => true,
        'notify_profile_views' => false,
        'suspicious_activity_alerts' => true,
    ],

    'content_moderation' => [
        'auto_moderate_bio' => true,
        'auto_moderate_skills' => false,
        'flag_inappropriate_content' => true,
        'require_admin_approval' => [
            'profile_pictures' => false,
            'documents' => true,
            'organization_affiliations' => true,
        ],
    ],

    'search_and_discovery' => [
        'allow_search_by_name' => true,
        'allow_search_by_skills' => true,
        'allow_search_by_location' => true,
        'allow_search_by_organization' => true,
        'show_in_recommendations' => true,
        'allow_external_search_engines' => true,
    ],

    'notifications' => [
        'profile_view_notifications' => false,
        'message_notifications' => true,
        'connection_request_notifications' => true,
        'privacy_setting_change_notifications' => true,
        'security_alert_notifications' => true,
    ],

    'export_options' => [
        'formats' => ['json', 'pdf', 'csv'],
        'include_documents' => true,
        'include_activity_log' => false,
        'include_analytics' => true,
        'max_export_frequency' => 'monthly',
    ],
];