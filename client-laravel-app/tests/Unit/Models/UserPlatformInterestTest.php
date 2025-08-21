<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserPlatformInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPlatformInterestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_platform_interest_belongs_to_user()
    {
        $user = User::factory()->create();
        $interest = UserPlatformInterest::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $interest->user);
        $this->assertEquals($user->id, $interest->user->id);
    }

    public function test_notification_enabled_is_cast_to_boolean()
    {
        $interest = UserPlatformInterest::factory()->create(['notification_enabled' => 1]);
        $this->assertIsBool($interest->notification_enabled);
        $this->assertTrue($interest->notification_enabled);

        $interest = UserPlatformInterest::factory()->create(['notification_enabled' => 0]);
        $this->assertIsBool($interest->notification_enabled);
        $this->assertFalse($interest->notification_enabled);
    }

    public function test_interest_types_are_valid()
    {
        $validTypes = ['events', 'news', 'resources', 'forums', 'networking'];

        foreach ($validTypes as $type) {
            $interest = UserPlatformInterest::factory()->create(['interest_type' => $type]);
            $this->assertEquals($type, $interest->interest_type);
        }
    }

    public function test_interest_levels_are_valid()
    {
        $validLevels = ['low', 'medium', 'high'];

        foreach ($validLevels as $level) {
            $interest = UserPlatformInterest::factory()->create(['interest_level' => $level]);
            $this->assertEquals($level, $interest->interest_level);
        }
    }

    public function test_unique_user_interest_type_combination()
    {
        $user = User::factory()->create();
        $interestType = 'events';

        // Create first interest
        UserPlatformInterest::factory()->create([
            'user_id' => $user->id,
            'interest_type' => $interestType
        ]);

        // Attempting to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        UserPlatformInterest::factory()->create([
            'user_id' => $user->id,
            'interest_type' => $interestType
        ]);
    }
}