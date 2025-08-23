<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContentTagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // Status tags
            [
                'name' => 'Featured',
                'slug' => 'featured',
                'description' => 'Featured content that is highlighted across the platform',
                'color' => '#F59E0B',
                'icon' => 'fas fa-star',
                'type' => 'status',
                'parent_id' => null,
                'sort_order' => 1,
                'is_active' => true,
                'is_featured' => true,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['priority' => 'high', 'display_badge' => true]),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],
            [
                'name' => 'Popular',
                'slug' => 'popular',
                'description' => 'Popular content with high engagement',
                'color' => '#EF4444',
                'icon' => 'fas fa-fire',
                'type' => 'status',
                'parent_id' => null,
                'sort_order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['priority' => 'medium', 'display_badge' => true]),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],
            
            // Category tags
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Technology related content and innovations',
                'color' => '#3B82F6',
                'icon' => 'fas fa-laptop',
                'type' => 'category',
                'parent_id' => null,
                'sort_order' => 3,
                'is_active' => true,
                'is_featured' => false,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['seo_keywords' => 'technology,innovation,digital']),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'description' => 'Educational content and learning resources',
                'color' => '#10B981',
                'icon' => 'fas fa-graduation-cap',
                'type' => 'category',
                'parent_id' => null,
                'sort_order' => 4,
                'is_active' => true,
                'is_featured' => false,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['seo_keywords' => 'education,learning,academic']),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],
            [
                'name' => 'Health',
                'slug' => 'health',
                'description' => 'Health and wellness topics',
                'color' => '#EF4444',
                'icon' => 'fas fa-heart',
                'type' => 'category',
                'parent_id' => null,
                'sort_order' => 5,
                'is_active' => true,
                'is_featured' => false,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['seo_keywords' => 'health,wellness,medical']),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],
            [
                'name' => 'Environment',
                'slug' => 'environment',
                'description' => 'Environmental and sustainability topics',
                'color' => '#22C55E',
                'icon' => 'fas fa-leaf',
                'type' => 'category',
                'parent_id' => null,
                'sort_order' => 6,
                'is_active' => true,
                'is_featured' => false,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['seo_keywords' => 'environment,sustainability,green']),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],

            // Skill tags
            [
                'name' => 'Leadership',
                'slug' => 'leadership',
                'description' => 'Leadership skills and development',
                'color' => '#8B5CF6',
                'icon' => 'fas fa-crown',
                'type' => 'skill',
                'parent_id' => null,
                'sort_order' => 7,
                'is_active' => true,
                'is_featured' => false,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['skill_level' => 'all', 'category' => 'soft_skills']),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],

            // Topic tags
            [
                'name' => 'Career',
                'slug' => 'career',
                'description' => 'Career development and professional growth',
                'color' => '#8B5CF6',
                'icon' => 'fas fa-briefcase',
                'type' => 'topic',
                'parent_id' => null,
                'sort_order' => 8,
                'is_active' => true,
                'is_featured' => false,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['related_categories' => ['education', 'skill']]),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],

            // Industry tags
            [
                'name' => 'Non-Profit',
                'slug' => 'non-profit',
                'description' => 'Non-profit organizations and social impact',
                'color' => '#06B6D4',
                'icon' => 'fas fa-hands-helping',
                'type' => 'industry',
                'parent_id' => null,
                'sort_order' => 9,
                'is_active' => true,
                'is_featured' => false,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['sector' => 'social_impact']),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],

            // Location tags
            [
                'name' => 'Uganda',
                'slug' => 'uganda',
                'description' => 'Content related to Uganda',
                'color' => '#DC2626',
                'icon' => 'fas fa-map-marker-alt',
                'type' => 'location',
                'parent_id' => null,
                'sort_order' => 10,
                'is_active' => true,
                'is_featured' => false,
                'usage_count' => 0,
                'created_by' => 1,
                'metadata' => json_encode(['country_code' => 'UG', 'region' => 'East Africa']),
                'created' => Carbon::now(),
                'modified' => Carbon::now(),
            ],
        ];

        DB::table('content_tags')->insert($tags);
    }
}