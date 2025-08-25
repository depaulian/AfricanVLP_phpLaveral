<?php

namespace Database\Seeders;

use App\Models\OrganizationCategory;
use Illuminate\Database\Seeder;

class OrganizationCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Government Agencies',
                'description' => 'Organizations and agencies operated by the government',
                'is_active' => true,
            ],
            [
                'name' => 'Civil Society Organizations',
                'description' => 'Non-governmental organizations focused on social impact',
                'is_active' => true,
            ],
            [
                'name' => 'Private Sector',
                'description' => 'Privately owned businesses and corporations',
                'is_active' => true,
            ],
            [
                'name' => 'Community Based Organizations',
                'description' => 'Local organizations addressing community needs',
                'is_active' => true,
            ],
            [
                'name' => 'Other',
                'description' => 'Other types of organizations not covered by specific categories',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            OrganizationCategory::create($category);
        }
    }
}