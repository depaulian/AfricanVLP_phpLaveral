<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\City;
use App\Models\Country;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_belongs_to_user()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }

    public function test_user_profile_belongs_to_city()
    {
        $city = City::factory()->create();
        $profile = UserProfile::factory()->create(['city_id' => $city->id]);

        $this->assertInstanceOf(City::class, $profile->city);
        $this->assertEquals($city->id, $profile->city->id);
    }

    public function test_user_profile_belongs_to_country()
    {
        $country = Country::factory()->create();
        $profile = UserProfile::factory()->create(['country_id' => $country->id]);

        $this->assertInstanceOf(Country::class, $profile->country);
        $this->assertEquals($country->id, $profile->country->id);
    }

    public function test_age_attribute_calculation()
    {
        $birthDate = now()->subYears(25);
        $profile = UserProfile::factory()->create(['date_of_birth' => $birthDate]);

        $this->assertEquals(25, $profile->age);
    }

    public function test_age_attribute_returns_null_when_no_birth_date()
    {
        $profile = UserProfile::factory()->create(['date_of_birth' => null]);

        $this->assertNull($profile->age);
    }

    public function test_full_address_attribute()
    {
        $city = City::factory()->create(['name' => 'Test City']);
        $country = Country::factory()->create(['name' => 'Test Country']);
        $profile = UserProfile::factory()->create([
            'address' => '123 Test Street',
            'city_id' => $city->id,
            'country_id' => $country->id
        ]);

        $expectedAddress = '123 Test Street, Test City, Test Country';
        $this->assertEquals($expectedAddress, $profile->full_address);
    }

    public function test_full_address_attribute_with_partial_data()
    {
        $profile = UserProfile::factory()->create([
            'address' => '123 Test Street',
            'city_id' => null,
            'country_id' => null
        ]);

        $this->assertEquals('123 Test Street', $profile->full_address);
    }

    public function test_calculate_completion_percentage_with_basic_fields()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'date_of_birth' => now()->subYears(25),
            'phone_number' => '+1234567890',
            'address' => '123 Test Street',
            'city_id' => City::factory()->create()->id,
            'profile_image_url' => 'test-image.jpg'
        ]);

        $percentage = $profile->calculateCompletionPercentage();

        $this->assertGreaterThan(0, $percentage);
        $this->assertLessThanOrEqual(100, $percentage);
        $this->assertEquals($percentage, $profile->fresh()->profile_completion_percentage);
    }

    public function test_calculate_completion_percentage_with_related_data()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        
        // Add related data
        UserSkill::factory()->create(['user_id' => $user->id]);
        UserVolunteeringInterest::factory()->create(['user_id' => $user->id]);
        UserVolunteeringHistory::factory()->create(['user_id' => $user->id]);

        $percentage = $profile->calculateCompletionPercentage();

        $this->assertGreaterThan(0, $percentage);
        $this->assertLessThanOrEqual(100, $percentage);
    }

    public function test_settings_are_cast_to_array()
    {
        $settings = ['privacy' => 'public', 'notifications' => true];
        $profile = UserProfile::factory()->create(['settings' => $settings]);

        $this->assertIsArray($profile->settings);
        $this->assertEquals($settings, $profile->settings);
    }

    public function test_is_public_is_cast_to_boolean()
    {
        $profile = UserProfile::factory()->create(['is_public' => 1]);
        $this->assertIsBool($profile->is_public);
        $this->assertTrue($profile->is_public);

        $profile = UserProfile::factory()->create(['is_public' => 0]);
        $this->assertIsBool($profile->is_public);
        $this->assertFalse($profile->is_public);
    }

    public function test_date_of_birth_is_cast_to_date()
    {
        $date = '1995-05-15';
        $profile = UserProfile::factory()->create(['date_of_birth' => $date]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $profile->date_of_birth);
        $this->assertEquals($date, $profile->date_of_birth->format('Y-m-d'));
    }
}