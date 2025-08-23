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
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Internships',
                'slug' => 'internships',
                'description' => 'Internship and training programs',
                'color' => '#3B82F6',
                'icon' => 'fas fa-user-graduate',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Scholarships',
                'slug' => 'scholarships',
                'description' => 'Educational scholarships and grants',
                'color' => '#F59E0B',
                'icon' => 'fas fa-graduation-cap',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Volunteer',
                'slug' => 'volunteer',
                'description' => 'Volunteer opportunities',
                'color' => '#EF4444',
                'icon' => 'fas fa-hands-helping',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Competitions',
                'slug' => 'competitions',
                'description' => 'Contests and competitions',
                'color' => '#8B5CF6',
                'icon' => 'fas fa-trophy',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('opportunity_categories')->insert($categories);
    }
}