<?php

namespace Tests\Feature\Profile;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\City;
use App\Models\Country;
use App\Models\VolunteeringCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_view_their_profile()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertViewIs('client.profile.show');
        $response->assertViewHas('user', $user);
    }

    public function test_user_can_view_another_users_public_profile()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $otherUser->id,
            'is_public' => true
        ]);

        $response = $this->actingAs($user)->get(route('profile.show', $otherUser));

        $response->assertStatus(200);
        $response->assertViewHas('user', $otherUser);
    }

    public function test_user_cannot_view_another_users_private_profile()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $otherUser->id,
            'is_public' => false
        ]);

        $response = $this->actingAs($user)->get(route('profile.show', $otherUser));

        $response->assertStatus(403);
    }

    public function test_user_can_access_profile_edit_page()
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $country = Country::factory()->create();
        $category = VolunteeringCategory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('client.profile.edit');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('cities');
        $response->assertViewHas('countries');
        $response->assertViewHas('categories');
    }

    public function test_user_can_update_their_profile()
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $country = Country::factory()->create();

        $profileData = [
            'bio' => 'Updated bio',
            'phone_number' => '+1234567890',
            'address' => '123 Updated Street',
            'city_id' => $city->id,
            'country_id' => $country->id,
            'linkedin_url' => 'https://linkedin.com/in/test',
            'is_public' => true
        ];

        $response = $this->actingAs($user)
            ->put(route('profile.update'), $profileData);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success', 'Profile updated successfully!');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Updated bio',
            'phone_number' => '+1234567890',
            'city_id' => $city->id,
            'country_id' => $country->id
        ]);
    }

    public function test_user_can_create_profile_if_none_exists()
    {
        $user = User::factory()->create();
        $city = City::factory()->create();

        $profileData = [
            'bio' => 'New bio',
            'phone_number' => '+1234567890',
            'city_id' => $city->id,
            'is_public' => true
        ];

        $response = $this->actingAs($user)
            ->put(route('profile.update'), $profileData);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success', 'Profile updated successfully!');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'New bio',
            'phone_number' => '+1234567890',
            'city_id' => $city->id
        ]);
    }

    public function test_user_can_upload_profile_image()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->actingAs($user)
            ->post(route('profile.upload-image'), [
                'image' => $file
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('path', $responseData);
        $this->assertArrayHasKey('url', $responseData);
        
        Storage::disk('public')->assertExists($responseData['path']);
    }

    public function test_profile_image_upload_validates_file_type()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('profile.upload-image'), [
                'image' => $file
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    public function test_profile_update_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('profile.update'), []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors();
    }

    public function test_profile_update_validates_email_format()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'linkedin_url' => 'invalid-url',
                'twitter_url' => 'invalid-url'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['linkedin_url', 'twitter_url']);
    }

    public function test_profile_update_validates_phone_number_format()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'phone_number' => 'invalid-phone'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['phone_number']);
    }

    public function test_guest_cannot_access_profile_pages()
    {
        $response = $this->get(route('profile.edit'));
        $response->assertRedirect(route('login'));

        $response = $this->put(route('profile.update'), []);
        $response->assertRedirect(route('login'));

        $response = $this->post(route('profile.upload-image'), []);
        $response->assertRedirect(route('login'));
    }

    public function test_profile_completion_percentage_updates_after_profile_update()
    {
        $user = User::factory()->create();
        $city = City::factory()->create();

        $profileData = [
            'bio' => 'Complete bio',
            'phone_number' => '+1234567890',
            'address' => '123 Complete Street',
            'city_id' => $city->id,
            'date_of_birth' => '1990-01-01',
            'is_public' => true
        ];

        $this->actingAs($user)
            ->put(route('profile.update'), $profileData);

        $user->refresh();
        $this->assertGreaterThan(0, $user->profile->profile_completion_percentage);
    }
}