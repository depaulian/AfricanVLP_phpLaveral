<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class FileUploadService
{
    protected $s3Client;
    protected $config;

    public function __construct()
    {
        // Try config first, then fallback to direct env access
        $this->config = [
            'bucket' => config('filesystems.disks.s3.bucket') ?: env('AWS_BUCKET'),
            'key' => config('filesystems.disks.s3.key') ?: env('AWS_ACCESS_KEY_ID'),
            'secret' => config('filesystems.disks.s3.secret') ?: env('AWS_SECRET_ACCESS_KEY'),
            'region' => config('filesystems.disks.s3.region') ?: env('AWS_DEFAULT_REGION', 'us-east-1'),
            'endpoint' => config('filesystems.disks.s3.endpoint') ?: env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => config('filesystems.disks.s3.use_path_style_endpoint') ?: env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'cloudfront_url' => env('AWS_CLOUDFRONT_URL'),
        ];
        
        // Validate required configuration
        if (empty($this->config['bucket'])) {
            throw new \Exception('S3 bucket name is not configured. Please check your AWS_BUCKET environment variable.');
        }
        
        if (empty($this->config['key']) || empty($this->config['secret'])) {
            throw new \Exception('S3 credentials are not configured. Please check AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY environment variables.');
        }
        
        if (empty($this->config['region'])) {
            throw new \Exception('S3 region is not configured. Please check AWS_DEFAULT_REGION environment variable.');
        }
        
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $this->config['region'],
            'credentials' => [
                'key' => $this->config['key'],
                'secret' => $this->config['secret'],
            ],
            'endpoint' => $this->config['endpoint'] ?? null,
            'use_path_style_endpoint' => $this->config['use_path_style_endpoint'] ?? false,
        ]);
        
        // Log configuration for debugging (without sensitive data)
        Log::info('S3 FileUploadService initialized', [
            'bucket' => $this->config['bucket'],
            'region' => $this->config['region'],
            'has_credentials' => !empty($this->config['key']) && !empty($this->config['secret']),
            'cloudfront_url' => $this->config['cloudfront_url'],
        ]);
    }

    /**
     * Upload a single file to S3
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function uploadFile(UploadedFile $file, string $folder = 'general', array $options = []): array
    {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateFilename($file);
            $key = $this->buildFileKey($folder, $filename, $file->getClientOriginalExtension());
            
            // Ensure bucket is set
            $bucket = $this->config['bucket'];
            if (empty($bucket)) {
                throw new \Exception('S3 bucket is not configured');
            }
            
            // Set upload options WITHOUT ACL (bucket has ACLs disabled)
            $uploadOptions = array_merge([
                'Bucket' => $bucket,
                'Key' => $key,
                'SourceFile' => $file->getPathname(),
                'ContentType' => $file->getMimeType(),
                'CacheControl' => 'max-age=31536000', // 1 year cache for better performance
                'Metadata' => [
                    'original-name' => $file->getClientOriginalName(),
                    'uploaded-at' => now()->toISOString(),
                ]
            ], $options);

            // Remove ACL if it exists in options (bucket doesn't support ACLs)
            unset($uploadOptions['ACL']);

            // Log upload attempt for debugging
            Log::info('Attempting S3 upload', [
                'bucket' => $bucket,
                'key' => $key,
                'file' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'note' => 'ACLs disabled on bucket'
            ]);

            // Upload to S3
            $result = $this->s3Client->putObject($uploadOptions);

            // Generate CloudFront URL if available, otherwise use S3 URL
            $url = $this->getPublicUrl($key);

            Log::info('S3 upload successful', ['key' => $key, 'etag' => $result['ETag']]);

            return [
                'success' => true,
                'key' => $key,
                'url' => $url, // CloudFront URL for better performance
                'secure_url' => $url,
                'etag' => trim($result['ETag'], '"'),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_filename' => $file->getClientOriginalName(),
                'folder' => $folder,
                'bucket' => $bucket
            ];

        } catch (AwsException $e) {
            Log::error('S3 upload failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'folder' => $folder,
                'aws_error_code' => $e->getAwsErrorCode(),
                'aws_error_type' => $e->getAwsErrorType(),
                'bucket' => $this->config['bucket'] ?? 'NOT_SET',
                'region' => $this->config['region'] ?? 'NOT_SET'
            ]);

            throw new \Exception('File upload failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'folder' => $folder,
                'bucket' => $this->config['bucket'] ?? 'NOT_SET'
            ]);

            throw new \Exception('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple files
     *
     * @param array $files
     * @param string $folder
     * @param array $options
     * @return array
     */
    public function uploadMultipleFiles(array $files, string $folder = 'general', array $options = []): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $results[] = $this->uploadFile($file, $folder, $options);
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => count($results) > 0,
            'uploaded' => $results,
            'errors' => $errors,
            'total_uploaded' => count($results),
            'total_errors' => count($errors)
        ];
    }

    /**
     * Delete a file from S3
     *
     * @param string $key
     * @return bool
     */
    public function deleteFile(string $key): bool
    {
        try {
            $result = $this->s3Client->deleteObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $key
            ]);

            return true;
        } catch (AwsException $e) {
            Log::error('S3 file deletion failed: ' . $e->getMessage(), [
                'key' => $key,
                'aws_error_code' => $e->getAwsErrorCode()
            ]);

            return false;
        }
    }

    /**
     * Delete multiple files from S3
     *
     * @param array $keys
     * @return array
     */
    public function deleteMultipleFiles(array $keys): array
    {
        try {
            $objects = array_map(function($key) {
                return ['Key' => $key];
            }, $keys);

            $result = $this->s3Client->deleteObjects([
                'Bucket' => $this->config['bucket'],
                'Delete' => [
                    'Objects' => $objects
                ]
            ]);

            return [
                'success' => true,
                'deleted' => $result['Deleted'] ?? [],
                'errors' => $result['Errors'] ?? []
            ];
        } catch (AwsException $e) {
            Log::error('S3 bulk deletion failed: ' . $e->getMessage(), [
                'keys' => $keys,
                'aws_error_code' => $e->getAwsErrorCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate presigned URL for temporary access
     *
     * @param string $key
     * @param string $expiration
     * @return string
     */
    public function getPresignedUrl(string $key, string $expiration = '+1 hour'): string
    {
        try {
            $command = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->config['bucket'],
                'Key' => $key
            ]);

            $request = $this->s3Client->createPresignedRequest($command, $expiration);
            
            return (string) $request->getUri();
        } catch (AwsException $e) {
            Log::error('Failed to generate presigned URL: ' . $e->getMessage(), [
                'key' => $key
            ]);

            throw new \Exception('Failed to generate download URL');
        }
    }

    /**
     * Get public URL - CloudFront if available, otherwise S3
     *
     * @param string $key
     * @return string
     */
    public function getPublicUrl(string $key): string
    {
        if (!empty($this->config['cloudfront_url'])) {
            $cloudfrontUrl = rtrim($this->config['cloudfront_url'], '/');
            return $cloudfrontUrl . '/' . ltrim($key, '/');
        }
        
        // Fallback to S3 URL
        return $this->s3Client->getObjectUrl($this->config['bucket'], $key);
    }

    /**
     * Upload profile image with resizing
     *
     * @param UploadedFile $file
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function uploadProfileImage(UploadedFile $file, int $userId): array
    {
        // Validate that it's an image
        if (!$this->isImageFile($file)) {
            throw new \Exception('File must be an image');
        }

        // Create resized versions
        $originalImage = Image::read($file->getPathname());
        
        // Create profile image (400x400)
        $profileImage = clone $originalImage;
        $profileImage->cover(400, 400);
        
        // Create thumbnail (150x150)
        $thumbnailImage = clone $originalImage;
        $thumbnailImage->cover(150, 150);

        $filename = "profile_{$userId}_" . time();
        
        try {
            // Upload original
            $originalKey = $this->buildFileKey('profiles', $filename . '_original', 'jpg');
            $this->uploadImageToS3($profileImage, $originalKey);

            // Upload thumbnail
            $thumbnailKey = $this->buildFileKey('profiles/thumbnails', $filename . '_thumb', 'jpg');
            $this->uploadImageToS3($thumbnailImage, $thumbnailKey);

            return [
                'success' => true,
                'original' => [
                    'key' => $originalKey,
                    'url' => $this->getPublicUrl($originalKey)
                ],
                'thumbnail' => [
                    'key' => $thumbnailKey,
                    'url' => $this->getPublicUrl($thumbnailKey)
                ],
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName()
            ];
        } catch (\Exception $e) {
            throw new \Exception('Profile image upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload organization logo with resizing
     *
     * @param UploadedFile $file
     * @param int $organizationId
     * @return array
     * @throws \Exception
     */
    public function uploadOrganizationLogo(UploadedFile $file, int $organizationId): array
    {
        // Validate that it's an image
        if (!$this->isImageFile($file)) {
            throw new \Exception('File must be an image');
        }

        $originalImage = Image::read($file->getPathname());
        
        // Resize logo (300x300 max, maintain aspect ratio)
        $logoImage = clone $originalImage;
        $logoImage->scaleDown(300, 300);

        $filename = "org_logo_{$organizationId}_" . time();
        
        try {
            $logoKey = $this->buildFileKey('organizations', $filename, 'png');
            $this->uploadImageToS3($logoImage, $logoKey, 'png');

            return [
                'success' => true,
                'key' => $logoKey,
                'url' => $this->getPublicUrl($logoKey),
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName()
            ];
        } catch (\Exception $e) {
            throw new \Exception('Organization logo upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate thumbnail for existing image
     *
     * @param string $sourceKey
     * @param string $thumbnailKey
     * @return array
     * @throws \Exception
     */
    public function generateThumbnail(string $sourceKey, string $thumbnailKey = null): array
    {
        try {
            // Download original image
            $result = $this->s3Client->getObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $sourceKey
            ]);

            $imageData = $result['Body']->getContents();
            $image = Image::read($imageData);
            
            // Create thumbnail
            $thumbnail = $image->cover(150, 150);
            
            // Generate thumbnail key if not provided
            if (!$thumbnailKey) {
                $pathInfo = pathinfo($sourceKey);
                $thumbnailKey = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            }

            $this->uploadImageToS3($thumbnail, $thumbnailKey);

            return [
                'success' => true,
                'key' => $thumbnailKey,
                'url' => $this->getPublicUrl($thumbnailKey)
            ];
        } catch (\Exception $e) {
            throw new \Exception('Thumbnail generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if file exists in S3
     *
     * @param string $key
     * @return bool
     */
    public function fileExists(string $key): bool
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $key
            ]);
            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    /**
     * Get file metadata
     *
     * @param string $key
     * @return array|null
     */
    public function getFileMetadata(string $key): ?array
    {
        try {
            $result = $this->s3Client->headObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $key
            ]);

            return [
                'key' => $key,
                'size' => $result['ContentLength'],
                'content_type' => $result['ContentType'],
                'last_modified' => $result['LastModified'],
                'etag' => trim($result['ETag'], '"'),
                'metadata' => $result['Metadata'] ?? []
            ];
        } catch (AwsException $e) {
            return null;
        }
    }

    /**
     * Copy file within S3
     *
     * @param string $sourceKey
     * @param string $destinationKey
     * @return bool
     */
    public function copyFile(string $sourceKey, string $destinationKey): bool
    {
        try {
            $this->s3Client->copyObject([
                'Bucket' => $this->config['bucket'],
                'CopySource' => $this->config['bucket'] . '/' . $sourceKey,
                'Key' => $destinationKey
            ]);

            return true;
        } catch (AwsException $e) {
            Log::error('S3 file copy failed: ' . $e->getMessage(), [
                'source' => $sourceKey,
                'destination' => $destinationKey
            ]);

            return false;
        }
    }

    /**
     * Test public access by trying to access a file
     *
     * @param string $key
     * @return bool
     */
    public function testPublicAccess(string $key): bool
    {
        $url = $this->getPublicUrl($key);
        
        // Try to access the URL
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 10
            ]
        ]);
        
        $headers = @get_headers($url, 1, $context);
        return $headers && strpos($headers[0], '200') !== false;
    }

    /**
     * Get multiple URLs at once (for efficiency)
     *
     * @param array $keys
     * @return array
     */
    public function getMultipleUrls(array $keys): array
    {
        $urls = [];
        foreach ($keys as $key) {
            $urls[$key] = $this->getPublicUrl($key);
        }
        return $urls;
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file size (default 10MB)
        $maxSize = config('app.max_file_size', 10 * 1024 * 1024);
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size');
        }

        // Check if file is valid
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Security check
        if (!$this->isSecureFile($file)) {
            throw new \Exception('File type not allowed for security reasons');
        }
    }

    /**
     * Generate unique filename
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $basename = Str::slug($basename);
        
        return $basename . '_' . time() . '_' . Str::random(8);
    }

    /**
     * Build file key for S3
     *
     * @param string $folder
     * @param string $filename
     * @param string $extension
     * @return string
     */
    protected function buildFileKey(string $folder, string $filename, string $extension): string
    {
        $folder = trim($folder, '/');
        $extension = strtolower($extension);
        
        return "{$folder}/{$filename}.{$extension}";
    }

    /**
     * Upload processed image to S3
     *
     * @param mixed $image Intervention Image instance
     * @param string $key
     * @param string $format
     * @return void
     * @throws \Exception
     */
    protected function uploadImageToS3($image, string $key, string $format = 'jpg'): void
    {
        $encodedImage = $image->encode($format, 90);
        
        $this->s3Client->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $key,
            'Body' => $encodedImage->toString(),
            'ContentType' => "image/{$format}",
            'CacheControl' => 'max-age=31536000', // 1 year cache
        ]);
    }

    /**
     * Check if file is an image
     *
     * @param UploadedFile $file
     * @return bool
     */
    protected function isImageFile(UploadedFile $file): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        return in_array($extension, $imageExtensions) && 
               str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Validate file against security checks
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function isSecureFile(UploadedFile $file): bool
    {
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'html', 'htm'];
        
        if (in_array($extension, $dangerousExtensions)) {
            return false;
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        $allowedMimes = [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml',
            // Documents
            'application/pdf', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv',
            // Videos
            'video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/webm',
            // Audio
            'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'
        ];

        return in_array($mimeType, $allowedMimes);
    }

    /**
     * Get file category based on MIME type
     *
     * @param string $mimeType
     * @return string
     */
    public function getFileCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'images';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'videos';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv'
        ])) {
            return 'documents';
        }
        
        return 'unknown';
    }

    /**
     * Get responsive image URLs for different screen sizes
     *
     * @param string $key
     * @return array
     */
    public function getResponsiveUrls(string $key): array
    {
        // For now, return the same URL for all sizes
        // You can implement image transformation service later if needed
        $baseUrl = $this->getPublicUrl($key);
        
        return [
            'small' => $baseUrl,
            'medium' => $baseUrl,
            'large' => $baseUrl,
            'original' => $baseUrl
        ];
    }

    /**
     * Extract S3 key from various URL formats
     *
     * @param string $url
     * @return string|null
     */
    public function extractKeyFromUrl(string $url): ?string
    {
        // Handle CloudFront URLs
        if (strpos($url, 'cloudfront.net') !== false) {
            $pattern = '/https:\/\/[^\/]+\.cloudfront\.net\/(.+)/';
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        // Handle direct S3 URLs
        $pattern = '/https:\/\/[^\/]+\.amazonaws\.com\/(.+)/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}