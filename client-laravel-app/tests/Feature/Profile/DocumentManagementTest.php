<?php

namespace Tests\Feature\Profile;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_user_can_view_documents_page()
    {
        $user = User::factory()->create();
        UserDocument::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('profile.documents.index'));

        $response->assertStatus(200);
        $response->assertViewIs('client.profile.documents');
        $response->assertViewHas('documents');
    }

    public function test_user_can_upload_document()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('profile.documents.store'), [
                'document' => $file,
                'document_type' => 'resume'
            ]);

        $response->assertRedirect(route('profile.documents.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_documents', [
            'user_id' => $user->id,
            'document_type' => 'resume',
            'file_name' => 'resume.pdf',
            'mime_type' => 'application/pdf',
            'verification_status' => 'pending'
        ]);

        $document = UserDocument::where('user_id', $user->id)->first();
        Storage::disk('private')->assertExists($document->file_path);
    }

    public function test_user_can_delete_their_document()
    {
        $user = User::factory()->create();
        $document = UserDocument::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->delete(route('profile.documents.destroy', $document));

        $response->assertRedirect(route('profile.documents.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('user_documents', ['id' => $document->id]);
        Storage::disk('private')->assertMissing($document->file_path);
    }

    public function test_user_can_download_their_document()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 1000, 'application/pdf');
        $path = $file->store('user-documents', 'private');
        
        $document = UserDocument::factory()->create([
            'user_id' => $user->id,
            'file_path' => $path,
            'file_name' => 'test.pdf',
            'mime_type' => 'application/pdf'
        ]);

        $response = $this->actingAs($user)
            ->get(route('profile.documents.download', $document));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="test.pdf"');
    }

    public function test_user_cannot_manage_other_users_documents()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = UserDocument::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->delete(route('profile.documents.destroy', $document));

        $response->assertStatus(403);

        $response = $this->actingAs($user)
            ->get(route('profile.documents.download', $document));

        $response->assertStatus(403);
    }

    public function test_document_upload_validates_file_type()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('malicious.exe', 1000, 'application/x-executable');

        $response = $this->actingAs($user)
            ->post(route('profile.documents.store'), [
                'document' => $file,
                'document_type' => 'other'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['document']);
    }

    public function test_document_upload_validates_file_size()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('large.pdf', 20000, 'application/pdf'); // 20MB

        $response = $this->actingAs($user)
            ->post(route('profile.documents.store'), [
                'document' => $file,
                'document_type' => 'resume'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['document']);
    }

    public function test_document_upload_validates_document_type()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('profile.documents.store'), [
                'document' => $file,
                'document_type' => 'invalid_type'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['document_type']);
    }

    public function test_documents_display_verification_status()
    {
        $user = User::factory()->create();
        $verifier = User::factory()->create();
        
        $pendingDoc = UserDocument::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'pending'
        ]);

        $verifiedDoc = UserDocument::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'verified',
            'verified_by' => $verifier->id,
            'verified_at' => now()
        ]);

        $rejectedDoc = UserDocument::factory()->create([
            'user_id' => $user->id,
            'verification_status' => 'rejected',
            'rejection_reason' => 'Document is not clear'
        ]);

        $response = $this->actingAs($user)->get(route('profile.documents.index'));

        $response->assertStatus(200);
        $response->assertSee('Pending');
        $response->assertSee('Verified');
        $response->assertSee('Rejected');
        $response->assertSee('Document is not clear');
    }

    public function test_user_can_view_document_details()
    {
        $user = User::factory()->create();
        $document = UserDocument::factory()->create([
            'user_id' => $user->id,
            'file_name' => 'my-resume.pdf',
            'file_size' => 1048576, // 1MB
            'verification_status' => 'verified'
        ]);

        $response = $this->actingAs($user)
            ->get(route('profile.documents.show', $document));

        $response->assertStatus(200);
        $response->assertViewIs('client.profile.documents.show');
        $response->assertSee('my-resume.pdf');
        $response->assertSee('1 MB');
        $response->assertSee('Verified');
    }

    public function test_user_can_replace_document()
    {
        $user = User::factory()->create();
        $oldDocument = UserDocument::factory()->create(['user_id' => $user->id]);
        $newFile = UploadedFile::fake()->create('new-resume.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user)
            ->put(route('profile.documents.update', $oldDocument), [
                'document' => $newFile,
                'document_type' => 'resume'
            ]);

        $response->assertRedirect(route('profile.documents.index'));
        $response->assertSessionHas('success');

        $updatedDocument = UserDocument::find($oldDocument->id);
        $this->assertEquals('new-resume.pdf', $updatedDocument->file_name);
        $this->assertEquals('pending', $updatedDocument->verification_status);
    }

    public function test_guest_cannot_access_document_pages()
    {
        $document = UserDocument::factory()->create();

        $response = $this->get(route('profile.documents.index'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('profile.documents.store'), []);
        $response->assertRedirect(route('login'));

        $response = $this->get(route('profile.documents.download', $document));
        $response->assertRedirect(route('login'));
    }
}