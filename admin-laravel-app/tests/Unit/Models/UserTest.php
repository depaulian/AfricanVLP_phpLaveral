<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Organization;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_email_must_be_unique()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => 'test@example.com']);
    }

    public function test_user_password_is_hashed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('plaintext'),
        ]);

        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(Hash::check('plaintext', $user->password));
    }

    public function test_user_has_organizations_relationship()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        
        $user->organizations()->attach($organization->id);

        $this->assertTrue($user->organizations->contains($organization));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->organizations);
    }

    public function test_user_has_notifications_relationship()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->notifications->contains($notification));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->notifications);
    }

    public function test_user_can_check_if_belongs_to_organization()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        
        $this->assertFalse($user->belongsToOrganization($organization->id));
        
        $user->organizations()->attach($organization->id);
        
        $this->assertTrue($user->belongsToOrganization($organization->id));
    }

    public function test_user_full_name_accessor()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->full_name);
    }

    public function test_user_avatar_url_accessor()
    {
        $user = User::factory()->create(['avatar' => 'avatar.jpg']);
        
        $this->assertStringContainsString('avatar.jpg', $user->avatar_url);
    }

    public function test_user_is_admin_method()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user']);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($regularUser->isAdmin());
    }

    public function test_user_can_be_soft_deleted()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNotNull($user->fresh()->deleted_at);
    }

    public function test_user_email_verification()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        $this->assertFalse($user->hasVerifiedEmail());
        
        $user->markEmailAsVerified();
        
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_user_password_reset_token()
    {
        $user = User::factory()->create();
        
        $token = $user->createPasswordResetToken();
        
        $this->assertNotNull($token);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_user_last_login_tracking()
    {
        $user = User::factory()->create(['last_login_at' => null]);
        
        $this->assertNull($user->last_login_at);
        
        $user->updateLastLogin();
        
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_user_active_scope()
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);
        User::factory()->create(['status' => 'suspended']);

        $activeUsers = User::active()->get();
        
        $this->assertCount(1, $activeUsers);
        $this->assertEquals('active', $activeUsers->first()->status);
    }

    public function test_user_search_scope()
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $results = User::search('John')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->name);
    }

    public function test_user_preferences_json_casting()
    {
        $preferences = [
            'theme' => 'dark',
            'notifications' => true,
            'language' => 'en',
        ];

        $user = User::factory()->create(['preferences' => $preferences]);

        $this->assertEquals($preferences, $user->preferences);
        $this->assertEquals('dark', $user->preferences['theme']);
    }

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();
        
        $newData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
            'phone' => '+1234567890',
        ];

        $user->updateProfile($newData);

        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('Updated bio', $user->fresh()->bio);
        $this->assertEquals('+1234567890', $user->fresh()->phone);
    }
}