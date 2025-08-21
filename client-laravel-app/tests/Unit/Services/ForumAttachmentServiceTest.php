<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ForumAttachmentService;
use App\Models\ForumAttachment;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\Forum;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForumAttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ForumAttachmentService $service;
    private User $user;
    private Forum $forum;
    private ForumThread $thread;
    private ForumPost $post;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ForumAttachmentService();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->forum = Forum::factory()->create(['is_private' => false]);
        $this->thread = ForumThread::factory()->create(['forum_id' => $this->forum->id]);
        $this->post = ForumPost::factory()->create(['thread_id' => $this->thread->id]);
        
        // Mock storage
        Storage::fake('private');
    }

    public function test_uploads_valid_attachments()
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg', 100, 100)->size(1024),
            UploadedFile::fake()->create('test2.pdf', 2048, 'application/pdf'),
        ];

        $attachments = $this->service->uploadAttachments($this->post, $files);

        $this->assertCount(2, $attachments);
        $this->assertInstanceOf(ForumAttachment::class, $attachments[0]);
        $this->assertInstanceOf(ForumAttachment::class, $attachments[1]);
        
        // Check database records
        $this->assertDatabaseCount('forum_attachments', 2);
        
        // Check files were stored
        $this->assertTrue(Storage::disk('private')->exists($attachments[0]->file_path));
        $this->assertTrue(Storage::disk('private')->exists($attachments[1]->file_path));
    }

    public function test_rejects_oversized_files()
    {
        $files = [
            UploadedFile::fake()->create('large.pdf', 15 * 1024) // 15MB, over 10MB limit
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size');

        $this->service->uploadAttachments($this->post, $files);
    }

    public function test_rejects_invalid_mime_types()
    {
        $files = [
            UploadedFile::fake()->create('malicious.exe', 1024, 'application/x-executable')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File type not allowed');

        $this->service->uploadAttachments($this->post, $files);
    }

    public function test_rejects_dangerous_extensions()
    {
        $files = [
            UploadedFile::fake()->create('script.php', 1024, 'text/plain')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File extension not allowed for security reasons');

        $this->service->uploadAttachments($this->post, $files);
    }

    public function test_enforces_maximum_attachments_per_post()
    {
        // Create 5 existing attachments
        ForumAttachment::factory()->count(5)->create(['post_id' => $this->post->id]);

        $files = [
            UploadedFile::fake()->image('test.jpg', 100, 100)->size(1024)
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Maximum number of attachments');

        $this->service->uploadAttachments($this->post, $files);
    }

    public function test_downloads_attachment_and_increments_count()
    {
        $attachment = ForumAttachment::factory()->create([
            'post_id' => $this->post->id,
            'file_path' => 'test-file.pdf',
            'download_count' => 0
        ]);

        // Create fake file
        Storage::disk('private')->put($attachment->file_path, 'test content');

        $response = $this->service->downloadAttachment($attachment);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        
        // Check download count was incremented
        $this->assertEquals(1, $attachment->fresh()->download_count);
    }

    public function test_download_fails_for_missing_file()
    {
        $attachment = ForumAttachment::factory()->create([
            'post_id' => $this->post->id,
            'file_path' => 'nonexistent-file.pdf'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File not found');

        $this->service->downloadAttachment($attachment);
    }

    public function test_deletes_attachment_and_file()
    {
        $attachment = ForumAttachment::factory()->create([
            'post_id' => $this->post->id,
            'file_path' => 'test-file.pdf'
        ]);

        // Create fake file
        Storage::disk('private')->put($attachment->file_path, 'test content');

        $result = $this->service->deleteAttachment($attachment);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('forum_attachments', ['id' => $attachment->id]);
        $this->assertFalse(Storage::disk('private')->exists($attachment->file_path));
    }

    public function test_formats_bytes_correctly()
    {
        $this->assertEquals('1 KB', $this->service->formatBytes(1024));
        $this->assertEquals('1 MB', $this->service->formatBytes(1024 * 1024));
        $this->assertEquals('1.5 MB', $this->service->formatBytes(1024 * 1024 * 1.5));
        $this->assertEquals('500 B', $this->service->formatBytes(500));
    }

    public function test_gets_correct_file_icons()
    {
        $this->assertEquals('fas fa-image', $this->service->getFileIcon('image/jpeg'));
        $this->assertEquals('fas fa-file-pdf', $this->service->getFileIcon('application/pdf'));
        $this->assertEquals('fas fa-file-word', $this->service->getFileIcon('application/msword'));
        $this->assertEquals('fas fa-file-archive', $this->service->getFileIcon('application/zip'));
        $this->assertEquals('fas fa-file-code', $this->service->getFileIcon('text/html'));
        $this->assertEquals('fas fa-file', $this->service->getFileIcon('unknown/type'));
    }

    public function test_validates_user_access_to_attachment()
    {
        // Public forum - should allow access
        $publicAttachment = ForumAttachment::factory()->create([
            'post_id' => $this->post->id
        ]);

        $this->assertTrue($this->service->canUserAccessAttachment($publicAttachment, $this->user));

        // Private forum with user not in organization
        $organization = \App\Models\Organization::factory()->create();
        $privateForum = Forum::factory()->create([
            'is_private' => true,
            'organization_id' => $organization->id
        ]);
        $privateThread = ForumThread::factory()->create(['forum_id' => $privateForum->id]);
        $privatePost = ForumPost::factory()->create(['thread_id' => $privateThread->id]);
        $privateAttachment = ForumAttachment::factory()->create(['post_id' => $privatePost->id]);

        $this->assertFalse($this->service->canUserAccessAttachment($privateAttachment, $this->user));

        // Private forum with user in organization
        $organization->users()->attach($this->user);

        $this->assertTrue($this->service->canUserAccessAttachment($privateAttachment, $this->user));
    }

    public function test_gets_allowed_file_types()
    {
        $allowedTypes = $this->service->getAllowedFileTypes();

        $this->assertIsArray($allowedTypes);
        $this->assertArrayHasKey('Images', $allowedTypes);
        $this->assertArrayHasKey('Documents', $allowedTypes);
        $this->assertArrayHasKey('Archives', $allowedTypes);
        $this->assertArrayHasKey('Code', $allowedTypes);
        
        $this->assertContains('JPEG', $allowedTypes['Images']);
        $this->assertContains('PDF', $allowedTypes['Documents']);
        $this->assertContains('ZIP', $allowedTypes['Archives']);
        $this->assertContains('HTML', $allowedTypes['Code']);
    }

    public function test_gets_max_file_size()
    {
        $maxSize = $this->service->getMaxFileSize();
        $this->assertEquals('10 MB', $maxSize);
    }

    public function test_gets_max_attachments_per_post()
    {
        $maxAttachments = $this->service->getMaxAttachmentsPerPost();
        $this->assertEquals(5, $maxAttachments);
    }

    public function test_validates_image_files()
    {
        // Valid image
        $validImage = UploadedFile::fake()->image('test.jpg', 100, 100);
        $files = [$validImage];

        $attachments = $this->service->uploadAttachments($this->post, $files);
        $this->assertCount(1, $attachments);

        // Invalid image (not actually an image)
        $invalidImage = UploadedFile::fake()->create('fake.jpg', 1024, 'image/jpeg');
        $files = [$invalidImage];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid image file');

        $this->service->uploadAttachments($this->post, $files);
    }

    public function test_detects_double_extensions()
    {
        $files = [
            UploadedFile::fake()->create('document.pdf.exe', 1024, 'text/plain')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Double extension detected with dangerous file type');

        $this->service->uploadAttachments($this->post, $files);
    }

    public function test_generates_unique_filenames()
    {
        $files = [
            UploadedFile::fake()->create('test file.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('test file.pdf', 1024, 'application/pdf'), // Same name
        ];

        $attachments = $this->service->uploadAttachments($this->post, $files);

        $this->assertCount(2, $attachments);
        $this->assertNotEquals($attachments[0]->file_path, $attachments[1]->file_path);
        
        // Both should contain the slugified name but have different unique IDs
        $this->assertStringContainsString('test-file', $attachments[0]->file_path);
        $this->assertStringContainsString('test-file', $attachments[1]->file_path);
    }
}