<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserVolunteeringInterest;
use App\Models\VolunteeringCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserVolunteeringInterestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_volunteering_interest_belongs_to_user()
    {
        $user = User::factory()->create();
        $interest = UserVolunteeringInterest::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $interest->user);
        $this->assertEquals($user->id, $interest->user->id);
    }

    public function test_user_volunteering_interest_belongs_to_category()
    {
        $category = VolunteeringCategory::factory()->create();
        $interest = UserVolunteeringInterest::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(VolunteeringCategory::class, $interest->category);
        $this->assertEquals($category->id, $interest->category->id);
    }

    public function test_interest_level_label_attribute()
    {
        $interest = UserVolunteeringInterest::factory()->create(['interest_level' => 'high']);

        $this->assertEquals('High', $interest->interest_level_label);
    }

    public function test_high_interest_scope()
    {
        UserVolunteeringInterest::factory()->create(['interest_level' => 'high']);
        UserVolunteeringInterest::factory()->create(['interest_level' => 'medium']);
        UserVolunteeringInterest::factory()->create(['interest_level' => 'low']);

        $highInterests = UserVolunteeringInterest::highInterest()->get();

        $this->assertCount(1, $highInterests);
        $this->assertEquals('high', $highInterests->first()->interest_level);
    }

    public function test_interest_levels_are_valid()
    {
        $validLevels = ['low', 'medium', 'high'];

        foreach ($validLevels as $level) {
            $interest = UserVolunteeringInterest::factory()->create(['interest_level' => $level]);
            $this->assertEquals($level, $interest->interest_level);
        }
    }

    public function test_unique_user_category_combination()
    {
        $user = User::factory()->create();
        $category = VolunteeringCategory::factory()->create();

        // Create first interest
        UserVolunteeringInterest::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // Attempting to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        UserVolunteeringInterest::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);
    }
}