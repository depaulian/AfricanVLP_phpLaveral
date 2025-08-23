<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OpportunityCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Jobs',
                'slug' => 'jobs',
                'description' => 'Full-time and part-time job opportunities',
                'color' => '#10B981',
                'icon' => 'fas fa-briefcase',
                'status' => 'active',
                'sort_order' => 1,
                'meta_title' => 'Job Opportunities - AU VLP Platform',
                'meta_description' => 'Discover full-time and part-time job opportunities across various industries and sectors.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Internships',
                'slug' => 'internships',
                'description' => 'Internship and training programs',
                'color' => '#3B82F6',
                'icon' => 'fas fa-user-graduate',
                'status' => 'active',
                'sort_order' => 2,
                'meta_title' => 'Internship Programs - AU VLP Platform',
                'meta_description' => 'Find internship and training programs to gain valuable work experience and skills.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Scholarships',
                'slug' => 'scholarships',
                'description' => 'Educational scholarships and grants',
                'color' => '#F59E0B',
                'icon' => 'fas fa-graduation-cap',
                'status' => 'active',
                'sort_order' => 3,
                'meta_title' => 'Scholarships & Grants - AU VLP Platform',
                'meta_description' => 'Apply for educational scholarships and grants to fund your academic journey.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Volunteer',
                'slug' => 'volunteer',
                'description' => 'Volunteer opportunities',
                'color' => '#EF4444',
                'icon' => 'fas fa-hands-helping',
                'status' => 'active',
                'sort_order' => 4,
                'meta_title' => 'Volunteer Opportunities - AU VLP Platform',
                'meta_description' => 'Make a difference in your community through meaningful volunteer opportunities.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Competitions',
                'slug' => 'competitions',
                'description' => 'Contests and competitions',
                'color' => '#8B5CF6',
                'icon' => 'fas fa-trophy',
                'status' => 'active',
                'sort_order' => 5,
                'meta_title' => 'Competitions & Contests - AU VLP Platform',
                'meta_description' => 'Participate in various competitions and contests to showcase your skills and win prizes.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('opportunity_categories')->insert($categories);
    }
}