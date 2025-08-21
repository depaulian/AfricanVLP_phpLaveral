<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserRegistrationStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRegistrationStepTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_step_belongs_to_user()
    {
        $user = User::factory()->create();
        $step = UserRegistrationStep::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $step->user);
        $this->assertEquals($user->id, $step->user->id);
    }

    public function test_complete_method_updates_completion_fields()
    {
        $step = UserRegistrationStep::factory()->create(['is_completed' => false]);

        $step->complete();

        $fresh = $step->fresh();
        $this->assertTrue($fresh->is_completed);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_completed_scope()
    {
        UserRegistrationStep::factory()->create(['is_completed' => true]);
        UserRegistrationStep::factory()->create(['is_completed' => false]);

        $completedSteps = UserRegistrationStep::completed()->get();

        $this->assertCount(1, $completedSteps);
        $this->assertTrue($completedSteps->first()->is_completed);
    }

    public function test_pending_scope()
    {
        UserRegistrationStep::factory()->create(['is_completed' => true]);
        UserRegistrationStep::factory()->create(['is_completed' => false]);

        $pendingSteps = UserRegistrationStep::pending()->get();

        $this->assertCount(1, $pendingSteps);
        $this->assertFalse($pendingSteps->first()->is_completed);
    }

    public function test_step_data_is_cast_to_array()
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];
        $step = UserRegistrationStep::factory()->create(['step_data' => $data]);

        $this->assertIsArray($step->step_data);
        $this->assertEquals($data, $step->step_data);
    }

    public function test_is_completed_is_cast_to_boolean()
    {
        $step = UserRegistrationStep::factory()->create(['is_completed' => 1]);
        $this->assertIsBool($step->is_completed);
        $this->assertTrue($step->is_completed);

        $step = UserRegistrationStep::factory()->create(['is_completed' => 0]);
        $this->assertIsBool($step->is_completed);
        $this->assertFalse($step->is_completed);
    }

    public function test_completed_at_is_cast_to_datetime()
    {
        $datetime = now();
        $step = UserRegistrationStep::factory()->create(['completed_at' => $datetime]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $step->completed_at);
    }

    public function test_unique_user_step_combination()
    {
        $user = User::factory()->create();
        $stepName = 'basic_info';

        // Create first step
        UserRegistrationStep::factory()->create([
            'user_id' => $user->id,
            'step_name' => $stepName
        ]);

        // Attempting to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        UserRegistrationStep::factory()->create([
            'user_id' => $user->id,
            'step_name' => $stepName
        ]);
    }
}