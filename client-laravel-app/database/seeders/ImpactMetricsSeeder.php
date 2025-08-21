<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ImpactMetric;

class ImpactMetricsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $metrics = [
            // Social Impact Metrics
            [
                'name' => 'People Helped',
                'slug' => 'people-helped',
                'description' => 'Number of individuals directly assisted through volunteer activities',
                'unit' => 'people',
                'type' => 'quantitative',
                'category' => 'social',
                'icon' => 'fas fa-users',
                'color' => '#3B82F6',
                'sort_order' => 1,
            ],
            [
                'name' => 'Families Supported',
                'slug' => 'families-supported',
                'description' => 'Number of families that received support or assistance',
                'unit' => 'families',
                'type' => 'quantitative',
                'category' => 'social',
                'icon' => 'fas fa-home',
                'color' => '#10B981',
                'sort_order' => 2,
            ],
            [
                'name' => 'Community Events Organized',
                'slug' => 'community-events-organized',
                'description' => 'Number of community events planned and executed',
                'unit' => 'events',
                'type' => 'quantitative',
                'category' => 'social',
                'icon' => 'fas fa-calendar-alt',
                'color' => '#8B5CF6',
                'sort_order' => 3,
            ],
            [
                'name' => 'Meals Provided',
                'slug' => 'meals-provided',
                'description' => 'Number of meals prepared and distributed to those in need',
                'unit' => 'meals',
                'type' => 'quantitative',
                'category' => 'social',
                'icon' => 'fas fa-utensils',
                'color' => '#F59E0B',
                'sort_order' => 4,
            ],

            // Educational Impact Metrics
            [
                'name' => 'Students Taught',
                'slug' => 'students-taught',
                'description' => 'Number of students who received educational support or tutoring',
                'unit' => 'students',
                'type' => 'quantitative',
                'category' => 'educational',
                'icon' => 'fas fa-graduation-cap',
                'color' => '#EF4444',
                'sort_order' => 5,
            ],
            [
                'name' => 'Books Distributed',
                'slug' => 'books-distributed',
                'description' => 'Number of books given to students, libraries, or communities',
                'unit' => 'books',
                'type' => 'quantitative',
                'category' => 'educational',
                'icon' => 'fas fa-book',
                'color' => '#06B6D4',
                'sort_order' => 6,
            ],
            [
                'name' => 'Literacy Rate Improvement',
                'slug' => 'literacy-rate-improvement',
                'description' => 'Percentage improvement in literacy rates in target communities',
                'unit' => 'percentage',
                'type' => 'percentage',
                'category' => 'educational',
                'icon' => 'fas fa-chart-line',
                'color' => '#84CC16',
                'sort_order' => 7,
            ],
            [
                'name' => 'Training Sessions Conducted',
                'slug' => 'training-sessions-conducted',
                'description' => 'Number of educational or skill-building sessions delivered',
                'unit' => 'sessions',
                'type' => 'quantitative',
                'category' => 'educational',
                'icon' => 'fas fa-chalkboard-teacher',
                'color' => '#F97316',
                'sort_order' => 8,
            ],

            // Environmental Impact Metrics
            [
                'name' => 'Trees Planted',
                'slug' => 'trees-planted',
                'description' => 'Number of trees planted for reforestation or environmental restoration',
                'unit' => 'trees',
                'type' => 'quantitative',
                'category' => 'environmental',
                'icon' => 'fas fa-tree',
                'color' => '#22C55E',
                'sort_order' => 9,
            ],
            [
                'name' => 'Waste Collected',
                'slug' => 'waste-collected',
                'description' => 'Amount of waste collected during cleanup activities',
                'unit' => 'kg',
                'type' => 'quantitative',
                'category' => 'environmental',
                'icon' => 'fas fa-trash',
                'color' => '#DC2626',
                'sort_order' => 10,
            ],
            [
                'name' => 'Water Saved',
                'slug' => 'water-saved',
                'description' => 'Amount of water conserved through environmental initiatives',
                'unit' => 'liters',
                'type' => 'quantitative',
                'category' => 'environmental',
                'icon' => 'fas fa-tint',
                'color' => '#0EA5E9',
                'sort_order' => 11,
            ],
            [
                'name' => 'Carbon Footprint Reduced',
                'slug' => 'carbon-footprint-reduced',
                'description' => 'Amount of CO2 emissions reduced through environmental activities',
                'unit' => 'kg',
                'type' => 'quantitative',
                'category' => 'environmental',
                'icon' => 'fas fa-leaf',
                'color' => '#059669',
                'sort_order' => 12,
            ],

            // Health Impact Metrics
            [
                'name' => 'Health Screenings Conducted',
                'slug' => 'health-screenings-conducted',
                'description' => 'Number of health checkups or screenings provided to community members',
                'unit' => 'screenings',
                'type' => 'quantitative',
                'category' => 'health',
                'icon' => 'fas fa-stethoscope',
                'color' => '#EC4899',
                'sort_order' => 13,
            ],
            [
                'name' => 'Vaccinations Administered',
                'slug' => 'vaccinations-administered',
                'description' => 'Number of vaccines given to prevent disease',
                'unit' => 'vaccinations',
                'type' => 'quantitative',
                'category' => 'health',
                'icon' => 'fas fa-syringe',
                'color' => '#7C3AED',
                'sort_order' => 14,
            ],
            [
                'name' => 'Health Education Sessions',
                'slug' => 'health-education-sessions',
                'description' => 'Number of health awareness and education sessions conducted',
                'unit' => 'sessions',
                'type' => 'quantitative',
                'category' => 'health',
                'icon' => 'fas fa-heartbeat',
                'color' => '#BE185D',
                'sort_order' => 15,
            ],

            // Economic Impact Metrics
            [
                'name' => 'Jobs Created',
                'slug' => 'jobs-created',
                'description' => 'Number of employment opportunities created through volunteer initiatives',
                'unit' => 'jobs',
                'type' => 'quantitative',
                'category' => 'economic',
                'icon' => 'fas fa-briefcase',
                'color' => '#0D9488',
                'sort_order' => 16,
            ],
            [
                'name' => 'Microloans Facilitated',
                'slug' => 'microloans-facilitated',
                'description' => 'Number of small loans provided to support entrepreneurship',
                'unit' => 'loans',
                'type' => 'quantitative',
                'category' => 'economic',
                'icon' => 'fas fa-coins',
                'color' => '#D97706',
                'sort_order' => 17,
            ],
            [
                'name' => 'Skills Training Participants',
                'slug' => 'skills-training-participants',
                'description' => 'Number of people who completed vocational or skills training programs',
                'unit' => 'people',
                'type' => 'quantitative',
                'category' => 'economic',
                'icon' => 'fas fa-tools',
                'color' => '#7C2D12',
                'sort_order' => 18,
            ],

            // Community Impact Metrics
            [
                'name' => 'Infrastructure Projects Completed',
                'slug' => 'infrastructure-projects-completed',
                'description' => 'Number of community infrastructure projects finished',
                'unit' => 'projects',
                'type' => 'quantitative',
                'category' => 'community',
                'icon' => 'fas fa-hammer',
                'color' => '#92400E',
                'sort_order' => 19,
            ],
            [
                'name' => 'Community Partnerships Formed',
                'slug' => 'community-partnerships-formed',
                'description' => 'Number of new partnerships established with local organizations',
                'unit' => 'partnerships',
                'type' => 'quantitative',
                'category' => 'community',
                'icon' => 'fas fa-handshake',
                'color' => '#1E40AF',
                'sort_order' => 20,
            ],
            [
                'name' => 'Volunteer Hours Contributed',
                'slug' => 'volunteer-hours-contributed',
                'description' => 'Total hours of volunteer service provided to the community',
                'unit' => 'hours',
                'type' => 'quantitative',
                'category' => 'community',
                'icon' => 'fas fa-clock',
                'color' => '#7C3AED',
                'sort_order' => 21,
            ],
        ];

        foreach ($metrics as $metricData) {
            ImpactMetric::updateOrCreate(
                ['slug' => $metricData['slug']],
                $metricData
            );
        }
    }
}