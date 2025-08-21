<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_document_belongs_to_user()
    {
        $user = User::factory()->create();
        $document = UserDocument::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $document->user);
        $this->assertEquals($user->id, $document->user->id);
    }

    public function test_user_document_belongs_to_verifier()
    {
        $verifier = User::factory()->create();
        $document = UserDocument::factory()->create(['verified_by' => $verifier->id]);

        $this->assertInstanceOf(User::class, $document->verifier);
        $this->assertEquals($verifier->id, $document->verifier->id);
    }

    public function test_verify_method_updates_verification_fields()
    {
        $verifier = User::factory()->create();
        $document = UserDocument::factory()->create(['verification_status' => 'pending']);

        $document->verify($verifier);

        $fresh = $document->fresh();
        $this->assertEquals('verified', $fresh->verification_status);
        $this->assertEquals($verifier->id, $fresh->verified_by);
        $this->assertNotNull($fresh->verified_at);
        $this->assertNull($fresh->rejection_reason);
    }

    public function test_reject_method_updates_verification_fields()
    {
        $verifier = User::factory()->create();
        $document = UserDocument::factory()->create(['verification_status' => 'pending']);
        $reason = 'Document is not clear';

        $document->reject($verifier, $reason);

        $fresh = $document->fresh();
        $this->assertEquals('rejected', $fresh->verification_status);
        $this->assertEquals($verifier->id, $fresh->verified_by);
        $this->assertNotNull($fresh->verified_at);
        $this->assertEquals($reason, $fresh->rejection_reason);
    }

    public function test_file_size_human_attribute()
    {
        $document = UserDocument::factory()->create(['file_size' => 1024]);
        $this->assertEquals('1 KB', $document->file_size_human);

        $document = UserDocument::factory()->create(['file_size' => 1048576]);
        $this->assertEquals('1 MB', $document->file_size_human);

        $document = UserDocument::factory()->create(['file_size' => 500]);
        $this->assertEquals('500 B', $document->file_size_human);
    }

    public function test_download_url_attribute()
    {
        $document = UserDocument::factory()->create();
        
        $expectedUrl = route('profile.documents.download', $document);
        $this->assertEquals($expectedUrl, $document->download_url);
    }

    public function test_verified_scope()
    {
        UserDocument::factory()->create(['verification_status' => 'verified']);
        UserDocument::factory()->create(['verification_status' => 'pending']);
        UserDocument::factory()->create(['verification_status' => 'rejected']);

        $verifiedDocuments = UserDocument::verified()->get();

        $this->assertCount(1, $verifiedDocuments);
        $this->assertEquals('verified', $verifiedDocuments->first()->verification_status);
    }

    public function test_pending_scope()
    {
        UserDocument::factory()->create(['verification_status' => 'verified']);
        UserDocument::factory()->create(['verification_status' => 'pending']);
        UserDocument::factory()->create(['verification_status' => 'rejected']);

        $pendingDocuments = UserDocument::pending()->get();

        $this->assertCount(1, $pendingDocuments);
        $this->assertEquals('pending', $pendingDocuments->first()->verification_status);
    }

    public function test_verified_at_is_cast_to_datetime()
    {
        $datetime = now();
        $document = UserDocument::factory()->create(['verified_at' => $datetime]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $document->verified_at);
    }

    public function test_document_types_are_valid()
    {
        $validTypes = ['resume', 'certificate', 'id', 'transcript', 'other'];

        foreach ($validTypes as $type) {
            $document = UserDocument::factory()->create(['document_type' => $type]);
            $this->assertEquals($type, $document->document_type);
        }
    }

    public function test_verification_statuses_are_valid()
    {
        $validStatuses = ['pending', 'verified', 'rejected'];

        foreach ($validStatuses as $status) {
            $document = UserDocument::factory()->create(['verification_status' => $status]);
            $this->assertEquals($status, $document->verification_status);
        }
    }
}