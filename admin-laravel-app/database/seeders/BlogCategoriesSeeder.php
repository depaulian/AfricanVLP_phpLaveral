<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BlogCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Technology and innovation articles',
                'color' => '#3B82F6',
                'icon' => 'fas fa-laptop-code',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'description' => 'Educational content and resources',
                'color' => '#10B981',
                'icon' => 'fas fa-graduation-cap',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Career',
                'slug' => 'career',
                'description' => 'Career development and opportunities',
                'color' => '#F59E0B',
                'icon' => 'fas fa-briefcase',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Community',
                'slug' => 'community',
                'description' => 'Community news and events',
                'color' => '#EF4444',
                'icon' => 'fas fa-users',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Research',
                'slug' => 'research',
                'description' => 'Research and academic content',
                'color' => '#8B5CF6',
                'icon' => 'fas fa-microscope',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('blog_categories')->insert($categories);
    }
}