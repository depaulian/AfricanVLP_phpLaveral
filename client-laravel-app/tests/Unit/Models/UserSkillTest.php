<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserSkillTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_skill_belongs_to_user()
    {
        $user = User::factory()->create();
        $skill = UserSkill::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $skill->user);
        $this->assertEquals($user->id, $skill->user->id);
    }

    public function test_user_skill_belongs_to_verifier()
    {
        $verifier = User::factory()->create();
        $skill = UserSkill::factory()->create(['verified_by' => $verifier->id]);

        $this->assertInstanceOf(User::class, $skill->verifier);
        $this->assertEquals($verifier->id, $skill->verifier->id);
    }

    public function test_verify_method_updates_verification_fields()
    {
        $verifier = User::factory()->create();
        $skill = UserSkill::factory()->create(['verified' => false]);

        $skill->verify($verifier);

        $this->assertTrue($skill->fresh()->verified);
        $this->assertEquals($verifier->id, $skill->fresh()->verified_by);
        $this->assertNotNull($skill->fresh()->verified_at);
    }

    public function test_proficiency_label_attribute()
    {
        $skill = UserSkill::factory()->create(['proficiency_level' => 'intermediate']);

        $this->assertEquals('Intermediate', $skill->proficiency_label);
    }

    public function test_experience_description_attribute_with_years()
    {
        $skill = UserSkill::factory()->create(['years_experience' => 3]);

        $this->assertEquals('3 years experience', $skill->experience_description);
    }

    public function test_experience_description_attribute_with_one_year()
    {
        $skill = UserSkill::factory()->create(['years_experience' => 1]);

        $this->assertEquals('1 year experience', $skill->experience_description);
    }

    public function test_experience_description_attribute_without_years()
    {
        $skill = UserSkill::factory()->create(['years_experience' => null]);

        $this->assertEquals('No experience specified', $skill->experience_description);
    }

    public function test_verified_is_cast_to_boolean()
    {
        $skill = UserSkill::factory()->create(['verified' => 1]);
        $this->assertIsBool($skill->verified);
        $this->assertTrue($skill->verified);

        $skill = UserSkill::factory()->create(['verified' => 0]);
        $this->assertIsBool($skill->verified);
        $this->assertFalse($skill->verified);
    }

    public function test_verified_at_is_cast_to_datetime()
    {
        $datetime = now();
        $skill = UserSkill::factory()->create(['verified_at' => $datetime]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $skill->verified_at);
    }

    public function test_proficiency_levels_are_valid()
    {
        $validLevels = ['beginner', 'intermediate', 'advanced', 'expert'];

        foreach ($validLevels as $level) {
            $skill = UserSkill::factory()->create(['proficiency_level' => $level]);
            $this->assertEquals($level, $skill->proficiency_level);
        }
    }
}