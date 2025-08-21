<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeedbackTemplate;
use App\Models\User;

class FeedbackTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to create templates
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            $this->command->warn('No admin user found. Skipping feedback templates seeding.');
            return;
        }

        $feedbackTypes = [
            'volunteer_to_organization' => [
                'name' => 'Volunteer to Organization Feedback',
                'description' => 'Template for volunteers to provide feedback about their experience with the organization',
            ],
            'organization_to_volunteer' => [
                'name' => 'Organization to Volunteer Feedback',
                'description' => 'Template for organizations to evaluate volunteer performance',
            ],
            'supervisor_to_volunteer' => [
                'name' => 'Supervisor to Volunteer Feedback',
                'description' => 'Template for supervisors to provide feedback on volunteer work',
            ],
            'volunteer_to_supervisor' => [
                'name' => 'Volunteer to Supervisor Feedback',
                'description' => 'Template for volunteers to provide feedback about their supervisor',
            ],
            'beneficiary_to_volunteer' => [
                'name' => 'Beneficiary to Volunteer Feedback',
                'description' => 'Template for beneficiaries to provide feedback about volunteer services',
            ],
        ];

        foreach ($feedbackTypes as $type => $info) {
            FeedbackTemplate::create([
                'name' => $info['name'],
                'description' => $info['description'],
                'organization_id' => null, // Global template
                'feedback_type' => $type,
                'template_type' => 'rating_form',
                'rating_categories' => FeedbackTemplate::getDefaultRatingCategories(),
                'questions' => FeedbackTemplate::getDefaultQuestions($type),
                'tags' => \App\Models\VolunteerFeedback::getAvailableTags($type),
                'settings' => FeedbackTemplate::getDefaultSettings(),
                'is_active' => true,
                'is_default' => true,
                'created_by' => $admin->id,
            ]);
        }

        $this->command->info('Feedback templates seeded successfully.');
    }
}