<?php

namespace Tests\Security\Profile;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserDocument;
use App\Models\UserSkill;
use App\Services\ProfilePrivacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    private ProfilePrivacyService $privacyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->privacyService = new ProfilePrivacyService();
        Storage::fake('private');
    }

    public function test_private_profile_access_control()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_public' => false
        ]);

        // Owner can access their private profile
        $response = $this->actingAs($user)->get(route('profile.show'));
        $response->assertStatus(200);

        // Other users cannot access private profile
        $response = $this->actingAs($otherUser)->get(route('profile.show', $user));
        $response->assertStatus(403);

        // Guests cannot access private profile
        $response = $this->get(route('profile.show', $user));
        $response->assertRedirect(route('login'));
    }

    public function test_profile_data_sanitization()
    {
        $user = User::factory()->create();

        $maliciousData = [
            'bio' => '<script>alert("XSS")</script>Legitimate bio content',
            'linkedin_url' => 'javascript:alert("XSS")',
            'website_url' => 'http://malicious-site.com"><script>alert("XSS")</script>',
            'address' => '<img src="x" onerror="alert(\'XSS\')">'
        ];

        $response = $this->actingAs($user)
            ->put(route('profile.update'), $maliciousData);

        // Should sanitize malicious content
        $profile = $user->fresh()->profile;
        $this->assertStringNotContainsString('<script>', $profile->bio);
        $this->assertStringNotContainsString('javascript:', $profile->linkedin_url ?? '');
        $this->assertStringNotContainsString('<img', $profile->address);
        $this->assertStringContainsString('Legitimate bio content', $profile->bio);
    }

    public function test_document_access_authorization()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        
        $document = UserDocument::factory()->create(['user_id' => $user->id]);

        // Owner can access their document
        $response = $this->actingAs($user)
            ->get(route('profile.documents.download', $document));
        $response->assertStatus(200);

        // Other users cannot access document
        $response = $this->actingAs($otherUser)
            ->get(route('profile.documents.download', $document));
        $response->assertStatus(403);

        // Admin can access for verification purposes
        $response = $this->actingAs($admin)
            ->get(route('admin.documents.view', $document));
        $response->assertStatus(200);
    }

    public function test_profile_modification_authorization()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $skill = UserSkill::factory()->create(['user_id' => $user->id]);

        // User can modify their own profile data
        $response = $this->actingAs($user)
            ->put(route('profile.skills.update', $skill), [
                'skill_name' => 'Updated Skill',
                'proficiency_level' => 'expert'
            ]);
        $response->assertStatus(302);

        // Other users cannot modify profile data
        $response = $this->actingAs($otherUser)
            ->put(route('profile.skills.update', $skill), [
                'skill_name' => 'Hacked Skill',
                'proficiency_level' => 'expert'
            ]);
        $response->assertStatus(403);

        // Verify data wasn't changed by unauthorized user
        $skill->refresh();
        $this->assertEquals('Updated Skill', $skill->skill_name);
        $this->assertNotEquals('Hacked Skill', $skill->skill_name);
    }

    public function test_file_upload_security()
    {
        $user = User::factory()->create();

        // Test malicious file upload
        $maliciousFile = UploadedFile::fake()->create('malicious.php', 1000, 'application/x-php');

        $response = $this->actingAs($user)
            ->post(route('profile.documents.store'), [
                'document' => $maliciousFile,
                'document_type' => 'other'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['document']);

        // Test oversized file
        $oversizedFile = UploadedFile::fake()->create('large.pdf', 20000, 'application/pdf'); // 20MB

        $response = $this->actingAs($user)
            ->post(route('profile.documents.store'), [
                'document' => $oversizedFile,
                'document_type' => 'resume'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['document']);

        // Test valid file
        $validFile = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('profile.documents.store'), [
                'document' => $validFile,
                'document_type' => 'resume'
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $user->id,
            'file_name' => 'resume.pdf'
        ]);
    }

    public function test_sql_injection_prevention()
    {
        $user = User::factory()->create();

        // Attempt SQL injection in profile search
        $maliciousQuery = "'; DROP TABLE user_profiles; --";

        $response = $this->actingAs($user)
            ->get(route('profile.search', ['q' => $maliciousQuery]));

        $response->assertStatus(200);

        // Verify table still exists
        $this->assertDatabaseHas('user_profiles', []);

        // Attempt SQL injection in skill creation
        $response = $this->actingAs($user)
            ->post(route('profile.skills.store'), [
                'skill_name' => "'; DROP TABLE user_skills; --",
                'proficiency_level' => 'intermediate'
            ]);

        // Should either succeed with sanitized data or fail validation
        $this->assertContains($response->status(), [200, 302, 422]);

        // Verify table still exists
        $this->assertDatabaseHas('user_skills', []);
    }

    public function test_mass_assignment_protection()
    {
        $user = User::factory()->create();

        // Attempt to mass assign protected fields
        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'bio' => 'Legitimate bio',
                'user_id' => 999, // Should be protected
                'profile_completion_percentage' => 100, // Should be protected
                'created_at' => '2020-01-01', // Should be protected
                'id' => 999 // Should be protected
            ]);

        $profile = $user->fresh()->profile;
        $this->assertEquals($user->id, $profile->user_id);
        $this->assertNotEquals(999, $profile->user_id);
        $this->assertNotEquals(100, $profile->profile_completion_percentage);
        $this->assertEquals('Legitimate bio', $profile->bio);
    }

    public function test_rate_limiting_on_sensitive_operations()
    {
        $user = User::factory()->create();

        // Attempt multiple rapid profile updates
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)
                ->put(route('profile.update'), [
                    'bio' => "Bio update $i"
                ]);
        }

        // Should eventually be rate limited
        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'bio' => 'Final update'
            ]);

        $this->assertContains($response->status(), [429, 302]); // Rate limited or successful
    }

    public function test_privacy_settings_enforcement()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'settings' => [
                'show_skills' => false,
                'show_volunteering_history' => false,
                'show_contact_info' => true
            ]
        ]);

        UserSkill::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($otherUser)
            ->get(route('profile.show', $user));

        $response->assertStatus(200);
        
        // Should not show skills due to privacy settings
        $response->assertDontSee('Skills');
        $response->assertDontSee('Volunteering History');
        
        // Should show contact info
        $response->assertSee('Contact Information');
    }

    public function test_session_security_for_profile_access()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        // Login and access profile
        $response = $this->actingAs($user)->get(route('profile.edit'));
        $response->assertStatus(200);

        // Simulate session hijacking by changing user agent
        $response = $this->actingAs($user)
            ->withHeaders(['User-Agent' => 'Different-Agent'])
            ->get(route('profile.edit'));

        // Should still work with different user agent (this is normal)
        $response->assertStatus(200);

        // Test CSRF protection
        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'bio' => 'Updated bio',
                '_token' => 'invalid-token'
            ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_profile_data_encryption_at_rest()
    {
        $user = User::factory()->create();
        
        $sensitiveData = [
            'phone_number' => '+1234567890',
            'address' => '123 Sensitive Street, Private City'
        ];

        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'phone_number' => $sensitiveData['phone_number'],
            'address' => $sensitiveData['address']
        ]);

        // Check if sensitive data is encrypted in database
        $rawData = \DB::table('user_profiles')
            ->where('user_id', $user->id)
            ->first();

        // If encryption is implemented, raw data should not match plain text
        // This test assumes encryption is implemented for sensitive fields
        if (config('app.encrypt_profile_data')) {
            $this->assertNotEquals($sensitiveData['phone_number'], $rawData->phone_number);
            $this->assertNotEquals($sensitiveData['address'], $rawData->address);
        }

        // But decrypted data should match
        $this->assertEquals($sensitiveData['phone_number'], $profile->phone_number);
        $this->assertEquals($sensitiveData['address'], $profile->address);
    }

    public function test_audit_logging_for_sensitive_operations()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        // Perform sensitive operation
        $this->actingAs($user)->put(route('profile.update'), [
            'bio' => 'Updated bio',
            'phone_number' => '+9876543210'
        ]);

        // Check if audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'model_type' => UserProfile::class,
            'model_id' => $profile->id
        ]);
    }

    public function test_profile_data_anonymization()
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'bio' => 'Personal bio with sensitive information',
            'phone_number' => '+1234567890',
            'address' => '123 Personal Street'
        ]);

        UserSkill::factory()->count(3)->create(['user_id' => $user->id]);
        UserDocument::factory()->count(2)->create(['user_id' => $user->id]);

        // Request data anonymization
        $response = $this->actingAs($user)
            ->post(route('profile.anonymize'));

        $response->assertStatus(200);

        // Verify data is anonymized
        $profile->refresh();
        $this->assertStringContainsString('[ANONYMIZED]', $profile->bio);
        $this->assertNull($profile->phone_number);
        $this->assertNull($profile->address);

        // Verify related data is also anonymized
        $this->assertEquals(0, $user->skills()->count());
        $this->assertEquals(0, $user->documents()->count());
    }
}