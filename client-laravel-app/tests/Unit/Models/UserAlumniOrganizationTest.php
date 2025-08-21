<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserAlumniOrganization;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAlumniOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_alumni_organization_belongs_to_user()
    {
        $user = User::factory()->create();
        $alumni = UserAlumniOrganization::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $alumni->user);
        $this->assertEquals($user->id, $alumni->user->id);
    }

    public function test_user_alumni_organization_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $alumni = UserAlumniOrganization::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $alumni->organization);
        $this->assertEquals($organization->id, $alumni->organization->id);
    }

    public function test_user_alumni_organization_belongs_to_verifier()
    {
        $verifier = User::factory()->create();
        $alumni = UserAlumniOrganization::factory()->create(['verified_by' => $verifier->id]);

        $this->assertInstanceOf(User::class, $alumni->verifier);
        $this->assertEquals($verifier->id, $alumni->verifier->id);
    }

    public function test_verify_method_updates_verification_fields()
    {
        $verifier = User::factory()->create();
        $alumni = UserAlumniOrganization::factory()->create(['is_verified' => false]);

        $alumni->verify($verifier);

        $fresh = $alumni->fresh();
        $this->assertTrue($fresh->is_verified);
        $this->assertEquals($verifier->id, $fresh->verified_by);
        $this->assertNotNull($fresh->verified_at);
    }

    public function test_status_label_attribute()
    {
        $alumni = UserAlumniOrganization::factory()->create(['status' => 'graduate']);

        $this->assertEquals('Graduate', $alumni->status_label);
    }

    public function test_education_summary_attribute()
    {
        $alumni = UserAlumniOrganization::factory()->create([
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science',
            'graduation_year' => 2020
        ]);

        $expected = 'Bachelor of Science - Computer Science - 2020';
        $this->assertEquals($expected, $alumni->education_summary);
    }

    public function test_education_summary_attribute_with_partial_data()
    {
        $alumni = UserAlumniOrganization::factory()->create([
            'degree' => 'Bachelor of Science',
            'field_of_study' => null,
            'graduation_year' => 2020
        ]);

        $expected = 'Bachelor of Science - 2020';
        $this->assertEquals($expected, $alumni->education_summary);
    }

    public function test_verified_scope()
    {
        UserAlumniOrganization::factory()->create(['is_verified' => true]);
        UserAlumniOrganization::factory()->create(['is_verified' => false]);

        $verifiedAlumni = UserAlumniOrganization::verified()->get();

        $this->assertCount(1, $verifiedAlumni);
        $this->assertTrue($verifiedAlumni->first()->is_verified);
    }

    public function test_graduates_scope()
    {
        UserAlumniOrganization::factory()->create(['status' => 'graduate']);
        UserAlumniOrganization::factory()->create(['status' => 'student']);
        UserAlumniOrganization::factory()->create(['status' => 'faculty']);

        $graduates = UserAlumniOrganization::graduates()->get();

        $this->assertCount(1, $graduates);
        $this->assertEquals('graduate', $graduates->first()->status);
    }

    public function test_is_verified_is_cast_to_boolean()
    {
        $alumni = UserAlumniOrganization::factory()->create(['is_verified' => 1]);
        $this->assertIsBool($alumni->is_verified);
        $this->assertTrue($alumni->is_verified);

        $alumni = UserAlumniOrganization::factory()->create(['is_verified' => 0]);
        $this->assertIsBool($alumni->is_verified);
        $this->assertFalse($alumni->is_verified);
    }

    public function test_verified_at_is_cast_to_datetime()
    {
        $datetime = now();
        $alumni = UserAlumniOrganization::factory()->create(['verified_at' => $datetime]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $alumni->verified_at);
    }

    public function test_status_values_are_valid()
    {
        $validStatuses = ['student', 'graduate', 'faculty', 'staff'];

        foreach ($validStatuses as $status) {
            $alumni = UserAlumniOrganization::factory()->create(['status' => $status]);
            $this->assertEquals($status, $alumni->status);
        }
    }
}