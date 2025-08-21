<?php

namespace Tests\Feature\Profile;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SkillManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_skills_page()
    {
        $user = User::factory()->create();
        UserSkill::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('profile.skills.index'));

        $response->assertStatus(200);
        $response->assertViewIs('client.profile.skills');
        $response->assertViewHas('skills');
    }

    public function test_user_can_add_new_skill()
    {
        $user = User::factory()->create();

        $skillData = [
            'skill_name' => 'Laravel',
            'proficiency_level' => 'advanced',
            'years_experience' => 3
        ];

        $response = $this->actingAs($user)
            ->post(route('profile.skills.store'), $skillData);

        $response->assertRedirect(route('profile.skills.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_skills', [
            'user_id' => $user->id,
            'skill_name' => 'Laravel',
            'proficiency_level' => 'advanced',
            'years_experience' => 3
        ]);
    }

    public function test_user_can_update_existing_skill()
    {
        $user = User::factory()->create();
        $skill = UserSkill::factory()->create([
            'user_id' => $user->id,
            'skill_name' => 'PHP',
            'proficiency_level' => 'intermediate'
        ]);

        $updateData = [
            'skill_name' => 'PHP',
            'proficiency_level' => 'advanced',
            'years_experience' => 5
        ];

        $response = $this->actingAs($user)
            ->put(route('profile.skills.update', $skill), $updateData);

        $response->assertRedirect(route('profile.skills.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_skills', [
            'id' => $skill->id,
            'proficiency_level' => 'advanced',
            'years_experience' => 5
        ]);
    }

    public function test_user_can_delete_skill()
    {
        $user = User::factory()->create();
        $skill = UserSkill::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->delete(route('profile.skills.destroy', $skill));

        $response->assertRedirect(route('profile.skills.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('user_skills', ['id' => $skill->id]);
    }

    public function test_user_cannot_manage_other_users_skills()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $skill = UserSkill::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->put(route('profile.skills.update', $skill), [
                'skill_name' => 'Hacking',
                'proficiency_level' => 'expert'
            ]);

        $response->assertStatus(403);
    }

    public function test_skill_creation_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('profile.skills.store'), []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['skill_name', 'proficiency_level']);
    }

    public function test_skill_creation_validates_proficiency_level()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('profile.skills.store'), [
                'skill_name' => 'Laravel',
                'proficiency_level' => 'invalid_level'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['proficiency_level']);
    }

    public function test_skill_creation_validates_years_experience()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('profile.skills.store'), [
                'skill_name' => 'Laravel',
                'proficiency_level' => 'advanced',
                'years_experience' => -1
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['years_experience']);
    }

    public function test_user_can_request_skill_verification()
    {
        $user = User::factory()->create();
        $skill = UserSkill::factory()->create([
            'user_id' => $user->id,
            'verified' => false
        ]);

        $response = $this->actingAs($user)
            ->post(route('profile.skills.request-verification', $skill));

        $response->assertRedirect(route('profile.skills.index'));
        $response->assertSessionHas('success');

        // Should create a verification request or notification
        // Implementation depends on your verification workflow
    }

    public function test_verified_skills_display_verification_badge()
    {
        $user = User::factory()->create();
        $verifier = User::factory()->create();
        $skill = UserSkill::factory()->create([
            'user_id' => $user->id,
            'verified' => true,
            'verified_by' => $verifier->id,
            'verified_at' => now()
        ]);

        $response = $this->actingAs($user)->get(route('profile.skills.index'));

        $response->assertStatus(200);
        $response->assertSee('Verified');
        $response->assertSee($verifier->name);
    }

    public function test_skills_are_ordered_by_proficiency_and_verification()
    {
        $user = User::factory()->create();
        
        UserSkill::factory()->create([
            'user_id' => $user->id,
            'skill_name' => 'Beginner Skill',
            'proficiency_level' => 'beginner',
            'verified' => false
        ]);

        UserSkill::factory()->create([
            'user_id' => $user->id,
            'skill_name' => 'Expert Skill',
            'proficiency_level' => 'expert',
            'verified' => true
        ]);

        UserSkill::factory()->create([
            'user_id' => $user->id,
            'skill_name' => 'Advanced Skill',
            'proficiency_level' => 'advanced',
            'verified' => false
        ]);

        $response = $this->actingAs($user)->get(route('profile.skills.index'));

        $response->assertStatus(200);
        
        // Verified expert skills should appear first
        $content = $response->getContent();
        $expertPos = strpos($content, 'Expert Skill');
        $advancedPos = strpos($content, 'Advanced Skill');
        $beginnerPos = strpos($content, 'Beginner Skill');

        $this->assertLessThan($advancedPos, $expertPos);
        $this->assertLessThan($beginnerPos, $advancedPos);
    }
}