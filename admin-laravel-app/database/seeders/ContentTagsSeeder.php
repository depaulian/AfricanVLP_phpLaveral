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
        // Clear existing content tags (optional)
        DB::table('content_tags')->truncate();

        $tags = [
            // Status tags
            [
                'name' => 'Featured',
                'slug' => 'featured',
                'type' => 'status',
                'color' => '#F59E0B',
                'icon' => 'fas fa-star',
                'description' => null,
                'is_active' => true,
                'sort_order' => 1,
                'usage_count' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Popular',
                'slug' => 'popular',
                'type' => 'status',
                'color' => '#EF4444',
                'icon' => 'fas fa-fire',
                'description' => null,
                'is_active' => true,
                'sort_order' => 2,
                'usage_count' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            // Topic/Category tags
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'type' => 'category',
                'color' => '#3B82F6',
                'icon' => 'fas fa-laptop',
                'description' => 'Technology related content',
                'is_active' => true,
                'sort_order' => 3,
                'usage_count' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'type' => 'category',
                'color' => '#10B981',
                'icon' => 'fas fa-graduation-cap',
                'description' => 'Educational content and resources',
                'is_active' => true,
                'sort_order' => 4,
                'usage_count' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Career',
                'slug' => 'career',
                'type' => 'topic',
                'color' => '#8B5CF6',
                'icon' => 'fas fa-briefcase',
                'description' => null,
                'is_active' => true,
                'sort_order' => 5,
                'usage_count' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Health',
                'slug' => 'health',
                'type' => 'category',
                'color' => '#EF4444',
                'icon' => 'fas fa-heart',
                'description' => 'Health and wellness topics',
                'is_active' => true,
                'sort_order' => 6,
                'usage_count' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Environment',
                'slug' => 'environment',
                'type' => 'category',
                'color' => '#22C55E',
                'icon' => 'fas fa-leaf',
                'description' => 'Environmental and sustainability topics',
                'is_active' => true,
                'sort_order' => 7,
                'usage_count' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            // Skill tags
            [
                'name' => 'Leadership',
                'slug' => 'leadership',
                'type' => 'skill',
                'color' => '#8B5CF6',
                'icon' => 'fas fa-crown',
                'description' => 'Leadership skills and development',
                'is_active' => true,
                'sort_order' => 8,
                'usage_count' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('content_tags')->insert($tags);
    }
}