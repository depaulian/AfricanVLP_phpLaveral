<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VolunteeringCategory;
use Illuminate\Support\Str;

class VolunteeringCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $categories = [
            [
                'name' => 'Emergency Medical Assistance',
                'slug' => 'emergency-medical-assistance',
                'description' => 'Providing medical aid and healthcare services during emergencies',
                'icon_url' => 'icons/medical.svg',
                'color_code' => '#DC2626',
                'sort_order' => 1,
                'status' => 'active',
                'settings' => ['requires_certification' => true]
            ],
            [
                'name' => 'Disaster Response',
                'slug' => 'disaster-response',
                'description' => 'Responding to natural disasters and providing immediate relief',
                'icon_url' => 'icons/disaster-response.svg',
                'color_code' => '#F97316',
                'sort_order' => 2,
                'status' => 'active',
                'settings' => ['physical_fitness_required' => true]
            ],
            [
                'name' => 'Supporting Elderly',
                'slug' => 'supporting-elderly',
                'description' => 'Assisting senior citizens with daily activities and companionship',
                'icon_url' => 'icons/elderly-care.svg',
                'color_code' => '#2563EB',
                'sort_order' => 3,
                'status' => 'active',
                'settings' => ['background_check' => true]
            ],
            [
                'name' => 'Capacity Building & Trainings',
                'slug' => 'capacity-building-trainings',
                'description' => 'Conducting training sessions and workshops for skill development',
                'icon_url' => 'icons/training.svg',
                'color_code' => '#10B981',
                'sort_order' => 4,
                'status' => 'active',
                'settings' => []
            ],
            [
                'name' => 'Advocacy for Marginalized Groups',
                'slug' => 'advocacy-marginalized-groups',
                'description' => 'Advocating for the rights and needs of marginalized communities',
                'icon_url' => 'icons/advocacy.svg',
                'color_code' => '#8B5CF6',
                'sort_order' => 5,
                'status' => 'active',
                'settings' => []
            ],
            [
                'name' => 'Street Cleaning and Trash Collection',
                'slug' => 'street-cleaning-trash-collection',
                'description' => 'Cleaning public spaces and managing waste collection initiatives',
                'icon_url' => 'icons/cleaning.svg',
                'color_code' => '#059669',
                'sort_order' => 6,
                'status' => 'active',
                'settings' => ['physical_activity' => true]
            ],
            [
                'name' => 'Technology Transfer to Africa',
                'slug' => 'technology-transfer-africa',
                'description' => 'Facilitating technology transfer and digital skills development in Africa',
                'icon_url' => 'icons/tech-transfer.svg',
                'color_code' => '#6366F1',
                'sort_order' => 7,
                'status' => 'active',
                'settings' => []
            ],
            [
                'name' => 'Awareness Creation',
                'slug' => 'awareness-creation',
                'description' => 'Creating awareness about important social and environmental issues',
                'icon_url' => 'icons/awareness.svg',
                'color_code' => '#EC4899',
                'sort_order' => 8,
                'status' => 'active',
                'settings' => []
            ],
            [

                'name' => 'Sustainable Development Projects',
                'slug' => 'sustainable-development-projects',
                'description' => 'Participating in projects that promote sustainable development',
                'icon_url' => 'icons/sustainability.svg',
                'color_code' => '#34D399',
                'sort_order' => 9,
                'status' => 'active',
                'settings' => []
        ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Other volunteering opportunities not covered in main categories',
                'icon_url' => 'icons/other.svg',
                'color_code' => '#6B7280',
                'sort_order' => 10,
                'status' => 'active',
                'settings' => []
            ],
        ];

        // Create all categories
        foreach ($categories as $categoryData) {
            VolunteeringCategory::create($categoryData);
        }

        $this->command->info('Volunteering categories seeded successfully!');
        $this->command->info('Total categories created: ' . VolunteeringCategory::count());
        
        // Display the created categories
        $this->command->table(
            ['ID', 'Name', 'Slug', 'Status'],
            VolunteeringCategory::all(['id', 'name', 'slug', 'status'])->toArray()
        );
    }
}