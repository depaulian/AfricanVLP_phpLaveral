<?php

namespace Tests\Integration\Profile;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\DocumentManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class DocumentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private DocumentManagementService $documentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->documentService = new DocumentManagementService();
        Storage::fake('private');
        Notification::fake();
    }

    public function test_complete_document_verification_workflow()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

        // Step 1: User uploads document
        $document = $this->documentService->uploadDocument($user, $file, 'resume');

        $this->assertEquals('pending', $document->verification_status);
        $this->assertNull($document->verified_by);
        $this->assertNull($document->verified_at);

        // Step 2: Admin reviews and verifies document
        $response = $this->actingAs($admin)
            ->post(route('admin.documents.verify', $document), [
                'action' => 'verify',
                'notes' => 'Document looks good'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertEquals('verified', $document->verification_status);
        $this->assertEquals($admin->id, $document->verified_by);
        $this->assertNotNull($document->verified_at);

        // Step 3: User receives verification notification
        Notification::assertSentTo(
            $user,
            \App\Notifications\DocumentVerificationStatusChanged::class
        );
    }

    public function test_document_rejection_workflow()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $document = UserDocument::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'pending'
        ]);

        // Admin rejects document
        $response = $this->actingAs($admin)
            ->post(route('admin.documents.verify', $document), [
                'action' => 'reject',
                'rejection_reason' => 'Document is not clear enough'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertEquals('rejected', $document->verification_status);
        $this->assertEquals($admin->id, $document->verified_by);
        $this->assertEquals('Document is not clear enough', $document->rejection_reason);

        // User receives rejection notification
        Notification::assertSentTo(
            $user,
            \App\Notifications\DocumentVerificationStatusChanged::class
        );
    }

    public function test_document_resubmission_after_rejection()
    {
        $user = User::factory()->create();
        $rejectedDocument = UserDocument::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'rejected',
            'rejection_reason' => 'Document is not clear'
        ]);

        $newFile = UploadedFile::fake()->create('new-resume.pdf', 1000, 'application/pdf');

        // User resubmits document
        $response = $this->actingAs($user)
            ->put(route('profile.documents.update', $rejectedDocument), [
                'document' => $newFile,
                'document_type' => 'resume'
            ]);

        $response->assertRedirect(route('profile.documents.index'));
        $response->assertSessionHas('success');

        $rejectedDocument->refresh();
        $this->assertEquals('pending', $rejectedDocument->verification_status);
        $this->assertNull($rejectedDocument->rejection_reason);
        $this->assertEquals('new-resume.pdf', $rejectedDocument->file_name);
    }

    public function test_bulk_document_verification()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $users = User::factory()->count(3)->create();
        $documents = collect();

        foreach ($users as $user) {
            $documents->push(UserDocument::factory()->create([
                'user_id' => $user->id,
                'verification_status' => 'pending'
            ]));
        }

        // Admin performs bulk verification
        $response = $this->actingAs($admin)
            ->post(route('admin.documents.bulk-verify'), [
                'document_ids' => $documents->pluck('id')->toArray(),
                'action' => 'verify'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // All documents should be verified
        foreach ($documents as $document) {
            $document->refresh();
            $this->assertEquals('verified', $document->verification_status);
            $this->assertEquals($admin->id, $document->verified_by);
        }

        // All users should receive notifications
        foreach ($users as $user) {
            Notification::assertSentTo(
                $user,
                \App\Notifications\DocumentVerificationStatusChanged::class
            );
        }
    }

    public function test_document_expiration_tracking()
    {
        $user = User::factory()->create();
        $document = UserDocument::factory()->create([
            'user_id' => $user->id,
            'document_type' => 'id',
            'verification_status' => 'verified',
            'expires_at' => now()->addDays(30)
        ]);

        // Check expiration reminder is sent
        $this->artisan('documents:check-expirations');

        Notification::assertSentTo(
            $user,
            \App\Notifications\DocumentExpirationReminder::class
        );
    }

    public function test_document_verification_analytics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $users = User::factory()->count(10)->create();

        // Create documents with different statuses
        UserDocument::factory()->count(5)->create(['verification_status' => 'pending']);
        UserDocument::factory()->count(3)->create(['verification_status' => 'verified']);
        UserDocument::factory()->count(2)->create(['verification_status' => 'rejected']);

        $response = $this->actingAs($admin)
            ->get(route('admin.documents.analytics'));

        $response->assertStatus(200);
        $response->assertViewHas('analytics');

        $analytics = $response->viewData('analytics');
        $this->assertEquals(10, $analytics['total_documents']);
        $this->assertEquals(5, $analytics['pending_documents']);
        $this->assertEquals(3, $analytics['verified_documents']);
        $this->assertEquals(2, $analytics['rejected_documents']);
    }

    public function test_document_verification_permissions()
    {
        $user = User::factory()->create();
        $regularUser = User::factory()->create();
        $document = UserDocument::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'pending'
        ]);

        // Regular user cannot verify documents
        $response = $this->actingAs($regularUser)
            ->post(route('admin.documents.verify', $document), [
                'action' => 'verify'
            ]);

        $response->assertStatus(403);

        // Document owner cannot verify their own documents
        $response = $this->actingAs($user)
            ->post(route('admin.documents.verify', $document), [
                'action' => 'verify'
            ]);

        $response->assertStatus(403);
    }

    public function test_document_verification_audit_trail()
    {
        $user = User::factory()->create();
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);
        
        $document = UserDocument::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'pending'
        ]);

        // First admin verifies
        $document->verify($admin1);

        // Second admin changes to rejected
        $document->reject($admin2, 'Found issues upon review');

        // Check audit trail
        $auditLogs = $document->auditLogs()->get();
        $this->assertCount(2, $auditLogs);

        $verifyLog = $auditLogs->where('action', 'verified')->first();
        $this->assertEquals($admin1->id, $verifyLog->performed_by);

        $rejectLog = $auditLogs->where('action', 'rejected')->first();
        $this->assertEquals($admin2->id, $rejectLog->performed_by);
        $this->assertEquals('Found issues upon review', $rejectLog->notes);
    }

    public function test_document_verification_queue_processing()
    {
        $users = User::factory()->count(5)->create();
        $documents = collect();

        foreach ($users as $user) {
            $documents->push(UserDocument::factory()->create([
                'user_id' => $user->id,
                'verification_status' => 'pending',
                'created_at' => now()->subDays(rand(1, 10))
            ]));
        }

        // Process verification queue (oldest first)
        $response = $this->get(route('admin.documents.queue'));

        $response->assertStatus(200);
        $response->assertViewHas('documents');

        $queuedDocuments = $response->viewData('documents');
        
        // Should be ordered by creation date (oldest first)
        $dates = $queuedDocuments->pluck('created_at');
        $sortedDates = $dates->sort();
        $this->assertEquals($sortedDates->values(), $dates->values());
    }

    public function test_document_verification_statistics_update()
    {
        $user = User::factory()->create();
        $document = UserDocument::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'pending'
        ]);

        // Initial stats
        $initialStats = $this->documentService->getVerificationStatistics();
        $this->assertEquals(1, $initialStats['pending']);
        $this->assertEquals(0, $initialStats['verified']);

        // Verify document
        $admin = User::factory()->create(['role' => 'admin']);
        $document->verify($admin);

        // Updated stats
        $updatedStats = $this->documentService->getVerificationStatistics();
        $this->assertEquals(0, $updatedStats['pending']);
        $this->assertEquals(1, $updatedStats['verified']);
    }
}