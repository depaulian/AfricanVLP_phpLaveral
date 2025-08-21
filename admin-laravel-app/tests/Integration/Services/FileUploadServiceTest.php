<?php

namespace Tests\Integration\Services;

use App\Services\FileUploadService;
use App\Services\InputSanitizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class FileUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FileUploadService $fileUploadService;
    protected InputSanitizationService $sanitizationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fileUploadService = app(FileUploadService::class);
        $this->sanitizationService = app(InputSanitizationService::class);
        
        Storage::fake('public');
    }

    public function test_can_upload_valid_image_file()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $result = $this->fileUploadService->uploadFile($file, 'images');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertStringContainsString('.jpg', $result['path']);
    }

    public function test_can_upload_valid_document_file()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $result = $this->fileUploadService->uploadFile($file, 'documents');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertStringContainsString('.pdf', $result['path']);
    }

    public function test_rejects_oversized_files()
    {
        $file = UploadedFile::fake()->create('large.pdf', 15000); // 15MB

        $result = $this->fileUploadService->uploadFile($file, 'documents', 10240); // 10MB limit

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('size', $result['error']);
    }

    public function test_rejects_invalid_file_types()
    {
        $file = UploadedFile::fake()->create('script.php', 100, 'application/x-php');

        $result = $this->fileUploadService->uploadFile($file, 'images');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('type', $result['error']);
    }

    public function test_rejects_executable_files()
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-executable');

        $result = $this->fileUploadService->uploadFile($file, 'documents');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('security', $result['error']);
    }

    public function test_sanitizes_filename()
    {
        $file = UploadedFile::fake()->image('test<script>.jpg');

        $result = $this->fileUploadService->uploadFile($file, 'images');

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('<script>', $result['path']);
        $this->assertStringNotContainsString('<', $result['path']);
        $this->assertStringNotContainsString('>', $result['path']);
    }

    public function test_generates_unique_filenames()
    {
        $file1 = UploadedFile::fake()->image('test.jpg');
        $file2 = UploadedFile::fake()->image('test.jpg');

        $result1 = $this->fileUploadService->uploadFile($file1, 'images');
        $result2 = $this->fileUploadService->uploadFile($file2, 'images');

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertNotEquals($result1['path'], $result2['path']);
    }

    public function test_validates_image_dimensions()
    {
        $file = UploadedFile::fake()->image('huge.jpg', 5000, 5000);

        $result = $this->fileUploadService->uploadFile($file, 'images', 5120, [
            'max_width' => 2000,
            'max_height' => 2000,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('dimensions', $result['error']);
    }

    public function test_can_resize_images()
    {
        $file = UploadedFile::fake()->image('large.jpg', 1500, 1500);

        $result = $this->fileUploadService->uploadFile($file, 'images', 5120, [
            'resize' => true,
            'max_width' => 800,
            'max_height' => 600,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('resized', $result);
        $this->assertTrue($result['resized']);
    }

    public function test_can_upload_multiple_files()
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->image('test2.jpg'),
            UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ];

        $result = $this->fileUploadService->uploadMultipleFiles($files, 'mixed');

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['files']);
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertEquals(3, $result['successful']);
        $this->assertEquals(0, $result['failed']);
    }

    public function test_handles_partial_failures_in_multiple_upload()
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->create('script.php', 100, 'application/x-php'), // Should fail
            UploadedFile::fake()->image('test2.jpg'),
        ];

        $result = $this->fileUploadService->uploadMultipleFiles($files, 'images');

        $this->assertTrue($result['success']); // Overall success if at least one file uploaded
        $this->assertEquals(2, $result['successful']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_can_delete_uploaded_file()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $uploadResult = $this->fileUploadService->uploadFile($file, 'images');

        $this->assertTrue($uploadResult['success']);
        Storage::disk('public')->assertExists($uploadResult['path']);

        $deleteResult = $this->fileUploadService->deleteFile($uploadResult['path']);

        $this->assertTrue($deleteResult);
        Storage::disk('public')->assertMissing($uploadResult['path']);
    }

    public function test_handles_cloudinary_upload()
    {
        // Mock Cloudinary service
        $this->mock(\Cloudinary\Api\Upload\UploadApi::class, function ($mock) {
            $mock->shouldReceive('upload')
                 ->once()
                 ->andReturn([
                     'public_id' => 'test_image',
                     'secure_url' => 'https://res.cloudinary.com/test/image/upload/test_image.jpg',
                     'format' => 'jpg',
                     'bytes' => 12345,
                 ]);
        });

        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->fileUploadService->uploadToCloudinary($file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('public_id', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('test_image', $result['public_id']);
    }

    public function test_handles_cloudinary_upload_failure()
    {
        // Mock Cloudinary service to throw exception
        $this->mock(\Cloudinary\Api\Upload\UploadApi::class, function ($mock) {
            $mock->shouldReceive('upload')
                 ->once()
                 ->andThrow(new \Exception('Cloudinary error'));
        });

        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->fileUploadService->uploadToCloudinary($file);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Cloudinary error', $result['error']);
    }

    public function test_validates_file_content()
    {
        // Create a fake PHP file disguised as an image
        $maliciousContent = '<?php echo "malicious code"; ?>';
        $file = UploadedFile::fake()->createWithContent('fake.jpg', $maliciousContent);

        $result = $this->fileUploadService->uploadFile($file, 'images');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('content', $result['error']);
    }

    public function test_logs_upload_attempts()
    {
        $this->expectsEvents(\App\Events\FileUploaded::class);

        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->fileUploadService->uploadFile($file, 'images');

        $this->assertTrue($result['success']);
    }

    public function test_respects_storage_quota()
    {
        // Mock storage quota check
        $this->fileUploadService = Mockery::mock(FileUploadService::class)->makePartial();
        $this->fileUploadService->shouldReceive('checkStorageQuota')
                               ->andReturn(false);

        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->fileUploadService->uploadFile($file, 'images');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('quota', $result['error']);
    }

    public function test_can_get_file_metadata()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        $uploadResult = $this->fileUploadService->uploadFile($file, 'images');

        $metadata = $this->fileUploadService->getFileMetadata($uploadResult['path']);

        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('mime_type', $metadata);
        $this->assertArrayHasKey('dimensions', $metadata);
        $this->assertEquals('image/jpeg', $metadata['mime_type']);
    }

    public function test_can_generate_thumbnails()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        $uploadResult = $this->fileUploadService->uploadFile($file, 'images');

        $thumbnailResult = $this->fileUploadService->generateThumbnail(
            $uploadResult['path'],
            150,
            150
        );

        $this->assertTrue($thumbnailResult['success']);
        $this->assertArrayHasKey('thumbnail_path', $thumbnailResult);
        Storage::disk('public')->assertExists($thumbnailResult['thumbnail_path']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}