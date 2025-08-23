<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing pages (optional)
        DB::table('pages')->truncate();

        $pages = [
            // Main Website Pages
            [
                'title' => 'Home',
                'slug' => 'home',
                'content' => null,
                'meta_title' => 'Home - AU VLP Platform',
                'meta_description' => 'Welcome to AU VLP - Your platform for opportunities, community, and growth.',
                'meta_keywords' => 'AU VLP, opportunities, community, platform, home',
                'status' => 'published',
                'template' => 'client.pages.home',
                'sections' => json_encode([
                    'hero' => [
                        'title' => 'Welcome to AU VLP',
                        'subtitle' => 'Your gateway to opportunities and community growth',
                        'button_text' => 'Get Started',
                        'button_link' => '/register'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => false,
                    'show_sidebar' => false,
                    'is_homepage' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'About Us',
                'slug' => 'about',
                'content' => null,
                'meta_title' => 'About Us - AU VLP Platform',
                'meta_description' => 'Learn about our mission to connect individuals with opportunities and foster community growth.',
                'meta_keywords' => 'about us, AU VLP, mission, vision, team',
                'status' => 'published',
                'template' => 'client.pages.about',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'About AU VLP',
                        'subtitle' => 'Empowering communities through opportunities and connections'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'show_sidebar' => false
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'content' => null,
                'meta_title' => 'Contact Us - AU VLP Platform',
                'meta_description' => 'Get in touch with the AU VLP team for support, questions, or partnership opportunities.',
                'meta_keywords' => 'contact, support, AU VLP, get in touch',
                'status' => 'published',
                'template' => 'client.pages.contact',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Contact Us',
                        'subtitle' => 'We\'d love to hear from you'
                    ],
                    'contact_info' => [
                        'email' => 'info@auvlp.org',
                        'phone' => '+256 XXX XXX XXX'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'form_enabled' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // News Section Pages
            [
                'title' => 'News',
                'slug' => 'news',
                'content' => null,
                'meta_title' => 'News - AU VLP Platform',
                'meta_description' => 'Stay updated with the latest news and announcements from AU VLP.',
                'meta_keywords' => 'news, updates, announcements, AU VLP',
                'status' => 'published',
                'template' => 'client.news.index',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Latest News',
                        'subtitle' => 'Stay informed with our latest updates and announcements'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'show_sidebar' => true,
                    'pagination' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Events Section Pages
            [
                'title' => 'Events',
                'slug' => 'events',
                'content' => null,
                'meta_title' => 'Events - AU VLP Platform',
                'meta_description' => 'Discover upcoming events, workshops, and community gatherings.',
                'meta_keywords' => 'events, workshops, seminars, AU VLP, community',
                'status' => 'published',
                'template' => 'client.events.index',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Upcoming Events',
                        'subtitle' => 'Join us for exciting events and networking opportunities'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'show_sidebar' => true,
                    'calendar_view' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Blog Section Pages
            [
                'title' => 'Blog',
                'slug' => 'blog',
                'content' => null,
                'meta_title' => 'Blog - AU VLP Platform',
                'meta_description' => 'Read insightful articles, tips, and stories from our community.',
                'meta_keywords' => 'blog, articles, insights, AU VLP, community stories',
                'status' => 'published',
                'template' => 'client.blog.index',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Our Blog',
                        'subtitle' => 'Insights, stories, and knowledge from our community'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'show_sidebar' => true,
                    'featured_posts' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Resources Section Pages
            [
                'title' => 'Resources',
                'slug' => 'resources',
                'content' => null,
                'meta_title' => 'Resources - AU VLP Platform',
                'meta_description' => 'Access valuable resources, downloads, and educational materials.',
                'meta_keywords' => 'resources, downloads, materials, AU VLP, education',
                'status' => 'published',
                'template' => 'client.resources.index',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Resource Center',
                        'subtitle' => 'Find valuable resources to help you grow and succeed'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'show_sidebar' => true,
                    'downloadable' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Forums Section Pages
            [
                'title' => 'Community Forums',
                'slug' => 'forums',
                'content' => null,
                'meta_title' => 'Forums - AU VLP Platform',
                'meta_description' => 'Join discussions, ask questions, and connect with the community.',
                'meta_keywords' => 'forums, community, discussions, AU VLP, networking',
                'status' => 'published',
                'template' => 'client.forums.index',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Community Forums',
                        'subtitle' => 'Connect, discuss, and share with fellow members'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'show_sidebar' => true,
                    'user_login_required' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Support Section Pages
            [
                'title' => 'Support Center',
                'slug' => 'support',
                'content' => null,
                'meta_title' => 'Support - AU VLP Platform',
                'meta_description' => 'Get help and support for your AU VLP account and services.',
                'meta_keywords' => 'support, help, FAQ, AU VLP, assistance',
                'status' => 'published',
                'template' => 'client.support.index',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Support Center',
                        'subtitle' => 'We\'re here to help you succeed'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'show_sidebar' => true,
                    'search_enabled' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Organizations Section Pages
            [
                'title' => 'Organizations',
                'slug' => 'organizations',
                'content' => null,
                'meta_title' => 'Organizations - AU VLP Platform',
                'meta_description' => 'Discover organizations and institutions within our network.',
                'meta_keywords' => 'organizations, institutions, partners, AU VLP, network',
                'status' => 'published',
                'template' => 'client.organizations.index',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Our Partner Organizations',
                        'subtitle' => 'Meet the organizations making a difference'
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'show_sidebar' => true,
                    'grid_layout' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Legal Pages
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => null,
                'meta_title' => 'Privacy Policy - AU VLP Platform',
                'meta_description' => 'Learn how we protect and handle your personal information.',
                'meta_keywords' => 'privacy policy, data protection, AU VLP, privacy',
                'status' => 'published',
                'template' => 'client.pages.legal',
                'sections' => json_encode([
                    'document' => [
                        'type' => 'legal',
                        'title' => 'Privacy Policy',
                        'last_updated' => Carbon::now()->toDateString()
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'legal_document' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => null,
                'meta_title' => 'Terms of Service - AU VLP Platform',
                'meta_description' => 'Read our terms and conditions for using the AU VLP platform.',
                'meta_keywords' => 'terms of service, terms and conditions, AU VLP, legal',
                'status' => 'published',
                'template' => 'client.pages.legal',
                'sections' => json_encode([
                    'document' => [
                        'type' => 'legal',
                        'title' => 'Terms of Service',
                        'last_updated' => Carbon::now()->toDateString()
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'legal_document' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => null,
                'meta_title' => 'FAQ - Frequently Asked Questions',
                'meta_description' => 'Find answers to common questions about AU VLP platform and services.',
                'meta_keywords' => 'FAQ, frequently asked questions, help, AU VLP, support',
                'status' => 'published',
                'template' => 'client.pages.faq',
                'sections' => json_encode([
                    'intro' => [
                        'title' => 'Frequently Asked Questions',
                        'subtitle' => 'Find answers to common questions'
                    ],
                    'categories' => [
                        'Getting Started' => [],
                        'Account Management' => [],
                        'Platform Usage' => [],
                        'Technical Support' => []
                    ]
                ]),
                'settings' => json_encode([
                    'show_breadcrumbs' => true,
                    'searchable' => true,
                    'collapsible_sections' => true
                ]),
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('pages')->insert($pages);
    }
}