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
                'status' => 'active',
                'sort_order' => 1,
                'meta_title' => 'Technology Articles - AU VLP Blog',
                'meta_description' => 'Read about the latest technology trends, innovations, and digital transformation insights.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'description' => 'Educational content and resources',
                'color' => '#10B981',
                'icon' => 'fas fa-graduation-cap',
                'status' => 'active',
                'sort_order' => 2,
                'meta_title' => 'Education Articles - AU VLP Blog',
                'meta_description' => 'Explore educational resources, learning opportunities, and academic insights.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Career',
                'slug' => 'career',
                'description' => 'Career development and opportunities',
                'color' => '#F59E0B',
                'icon' => 'fas fa-briefcase',
                'status' => 'active',
                'sort_order' => 3,
                'meta_title' => 'Career Development - AU VLP Blog',
                'meta_description' => 'Get career advice, job search tips, and professional development insights.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Community',
                'slug' => 'community',
                'description' => 'Community news and events',
                'color' => '#EF4444',
                'icon' => 'fas fa-users',
                'status' => 'active',
                'sort_order' => 4,
                'meta_title' => 'Community News - AU VLP Blog',
                'meta_description' => 'Stay updated with community news, events, and member highlights.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Research',
                'slug' => 'research',
                'description' => 'Research and academic content',
                'color' => '#8B5CF6',
                'icon' => 'fas fa-microscope',
                'status' => 'active',
                'sort_order' => 5,
                'meta_title' => 'Research & Academia - AU VLP Blog',
                'meta_description' => 'Discover research findings, academic papers, and scholarly insights.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('blog_categories')->insert($categories);
    }
}