<?php

namespace Tests\Browser\Profile;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\City;
use App\Models\Country;
use App\Models\VolunteeringCategory;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ProfileInteractionTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_complete_profile_wizard()
    {
        $user = User::factory()->create();
        $city = City::factory()->create(['name' => 'Test City']);
        $country = Country::factory()->create(['name' => 'Test Country']);

        $this->browse(function (Browser $browser) use ($user, $city, $country) {
            $browser->loginAs($user)
                    ->visit('/profile/wizard')
                    ->assertSee('Complete Your Profile')
                    
                    // Step 1: Basic Information
                    ->type('bio', 'I am passionate about helping others through volunteering.')
                    ->type('phone_number', '+1234567890')
                    ->type('address', '123 Main Street')
                    ->select('city_id', $city->id)
                    ->select('country_id', $country->id)
                    ->click('@next-step')
                    
                    // Step 2: Skills
                    ->waitForText('Add Your Skills')
                    ->type('skill_name', 'Leadership')
                    ->select('proficiency_level', 'advanced')
                    ->type('years_experience', '5')
                    ->click('@add-skill')
                    ->waitForText('Leadership')
                    ->assertSee('Advanced')
                    ->click('@next-step')
                    
                    // Step 3: Interests
                    ->waitForText('Select Your Interests')
                    ->check('interests[]')
                    ->click('@next-step')
                    
                    // Step 4: Review and Complete
                    ->waitForText('Review Your Profile')
                    ->assertSee('I am passionate about helping others')
                    ->assertSee('Leadership')
                    ->assertSee('Test City')
                    ->click('@complete-profile')
                    
                    // Should redirect to profile page
                    ->waitForLocation('/profile')
                    ->assertSee('Profile completed successfully!');
        });

        // Verify data was saved
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'I am passionate about helping others through volunteering.',
            'phone_number' => '+1234567890',
            'city_id' => $city->id
        ]);

        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_name' => 'Leadership',
            'proficiency_level' => 'advanced',
            'years_experience' => 5
        ]);
    }

    public function test_user_can_edit_profile_inline()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Original bio'
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile')
                    ->assertSee('Original bio')
                    
                    // Click edit bio button
                    ->click('@edit-bio')
                    ->waitFor('@bio-editor')
                    ->clear('@bio-input')
                    ->type('@bio-input', 'Updated bio with more details')
                    ->click('@save-bio')
                    
                    // Should see updated content
                    ->waitForText('Updated bio with more details')
                    ->assertDontSee('Original bio')
                    ->assertSee('Profile updated successfully');
        });

        // Verify data was updated
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Updated bio with more details'
        ]);
    }

    public function test_user_can_upload_profile_image_with_cropping()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile/edit')
                    ->assertSee('Profile Image')
                    
                    // Upload image
                    ->attach('profile_image', __DIR__.'/../../fixtures/test-image.jpg')
                    ->waitFor('@image-cropper')
                    ->assertSee('Crop Your Image')
                    
                    // Adjust crop area (simulate drag)
                    ->drag('@crop-handle-nw', 10, 10)
                    ->drag('@crop-handle-se', -10, -10)
                    
                    // Save cropped image
                    ->click('@save-cropped-image')
                    ->waitForText('Image uploaded successfully')
                    
                    // Should see new profile image
                    ->waitFor('@profile-image')
                    ->assertPresent('@profile-image[src*="profile-images"]');
        });

        // Verify image was saved
        $user->refresh();
        $this->assertNotNull($user->profile->profile_image_url);
    }

    public function test_user_can_manage_skills_dynamically()
    {
        $user = User::factory()->create();
        UserSkill::factory()->create([
            'user_id' => $user->id,
            'skill_name' => 'Existing Skill',
            'proficiency_level' => 'intermediate'
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile/skills')
                    ->assertSee('Existing Skill')
                    ->assertSee('Intermediate')
                    
                    // Add new skill
                    ->click('@add-skill-btn')
                    ->waitFor('@skill-form')
                    ->type('@skill-name', 'New Skill')
                    ->select('@proficiency-level', 'expert')
                    ->type('@years-experience', '3')
                    ->click('@save-skill')
                    
                    // Should see new skill added
                    ->waitForText('New Skill')
                    ->assertSee('Expert')
                    ->assertSee('3 years')
                    
                    // Edit existing skill
                    ->click('@edit-skill-1')
                    ->waitFor('@edit-skill-form')
                    ->select('@edit-proficiency-level', 'advanced')
                    ->click('@update-skill')
                    
                    // Should see updated skill
                    ->waitForText('Advanced')
                    ->assertDontSee('Intermediate')
                    
                    // Delete skill
                    ->click('@delete-skill-2')
                    ->waitFor('@confirm-delete')
                    ->click('@confirm-delete-btn')
                    
                    // Should not see deleted skill
                    ->waitUntilMissing('@skill-item-2')
                    ->assertDontSee('New Skill');
        });
    }

    public function test_user_can_set_privacy_preferences()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_public' => true
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile/privacy')
                    ->assertSee('Privacy Settings')
                    
                    // Profile visibility
                    ->assertChecked('@profile-public')
                    ->uncheck('@profile-public')
                    ->check('@profile-private')
                    
                    // Section visibility
                    ->uncheck('@show-skills')
                    ->uncheck('@show-volunteering-history')
                    ->check('@show-contact-info')
                    
                    // Save settings
                    ->click('@save-privacy-settings')
                    ->waitForText('Privacy settings updated')
                    
                    // Verify settings were applied
                    ->visit('/profile')
                    ->assertSee('Private Profile')
                    ->assertDontSee('Skills')
                    ->assertDontSee('Volunteering History');
        });

        // Verify database was updated
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'is_public' => false
        ]);
    }

    public function test_user_can_view_profile_analytics()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'profile_completion_percentage' => 85
        ]);

        // Create some profile activity
        UserSkill::factory()->count(5)->create(['user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile/analytics')
                    ->assertSee('Profile Analytics')
                    
                    // Profile completion
                    ->assertSee('85%')
                    ->assertSee('Profile Completion')
                    
                    // Skills chart
                    ->waitFor('@skills-chart')
                    ->assertPresent('@skills-chart')
                    
                    // Profile views over time
                    ->waitFor('@views-chart')
                    ->assertPresent('@views-chart')
                    
                    // Recommendations
                    ->assertSee('Recommendations')
                    ->assertSee('Add more volunteering history')
                    
                    // Export profile data
                    ->click('@export-profile-data')
                    ->waitForText('Export requested')
                    ->assertSee('You will receive an email with your profile data');
        });
    }

    public function test_user_can_search_and_connect_with_other_users()
    {
        $user = User::factory()->create();
        $otherUsers = User::factory()->count(3)->create();
        
        foreach ($otherUsers as $otherUser) {
            UserProfile::factory()->create([
                'user_id' => $otherUser->id,
                'is_public' => true,
                'bio' => 'I love volunteering in ' . $otherUser->name . ' community'
            ]);
        }

        $this->browse(function (Browser $browser) use ($user, $otherUsers) {
            $browser->loginAs($user)
                    ->visit('/profile/network')
                    ->assertSee('Find Volunteers')
                    
                    // Search for users
                    ->type('@search-input', 'volunteering')
                    ->click('@search-btn')
                    ->waitForText('Search Results')
                    
                    // Should see matching profiles
                    ->assertSee($otherUsers[0]->name)
                    ->assertSee($otherUsers[1]->name)
                    
                    // View profile
                    ->click('@view-profile-' . $otherUsers[0]->id)
                    ->waitForLocation('/profile/' . $otherUsers[0]->id)
                    ->assertSee($otherUsers[0]->name)
                    ->assertSee('I love volunteering')
                    
                    // Send connection request
                    ->click('@connect-btn')
                    ->waitForText('Connection request sent')
                    ->assertSee('Request Pending');
        });
    }

    public function test_profile_completion_progress_updates_dynamically()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/profile/edit')
                    
                    // Initial completion should be low
                    ->assertSee('Profile Completion')
                    ->assertSeeIn('@completion-percentage', '0%')
                    
                    // Add bio
                    ->type('@bio', 'Test bio')
                    ->click('@save-section')
                    ->waitForText('Section saved')
                    
                    // Completion should increase
                    ->waitUntilSeeIn('@completion-percentage', function ($text) {
                        return intval($text) > 0;
                    })
                    
                    // Add phone number
                    ->type('@phone-number', '+1234567890')
                    ->click('@save-section')
                    ->waitForText('Section saved')
                    
                    // Completion should increase further
                    ->waitUntilSeeIn('@completion-percentage', function ($text) {
                        return intval($text) > 20;
                    })
                    
                    // Add skills
                    ->click('@add-skill')
                    ->type('@skill-name', 'Test Skill')
                    ->select('@proficiency-level', 'intermediate')
                    ->click('@save-skill')
                    ->waitForText('Skill added')
                    
                    // Completion should increase more
                    ->waitUntilSeeIn('@completion-percentage', function ($text) {
                        return intval($text) > 40;
                    });
        });
    }

    public function test_mobile_responsive_profile_interface()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->resize(375, 667) // iPhone size
                    ->visit('/profile')
                    
                    // Mobile navigation should be present
                    ->assertPresent('@mobile-nav')
                    ->assertPresent('@hamburger-menu')
                    
                    // Profile sections should stack vertically
                    ->assertPresent('@profile-header')
                    ->assertPresent('@profile-content')
                    
                    // Tap hamburger menu
                    ->click('@hamburger-menu')
                    ->waitFor('@mobile-menu')
                    ->assertSee('Edit Profile')
                    ->assertSee('Privacy Settings')
                    
                    // Edit profile on mobile
                    ->click('@edit-profile-mobile')
                    ->waitForLocation('/profile/edit')
                    
                    // Mobile form should be touch-friendly
                    ->assertPresent('@mobile-form')
                    ->tap('@bio-field')
                    ->type('@bio-field', 'Mobile edited bio')
                    ->tap('@save-btn')
                    ->waitForText('Profile updated');
        });
    }
}