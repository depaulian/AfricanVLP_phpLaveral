<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Quality;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileUploadService
{
    protected $cloudinary;
    protected $config;

    public function __construct()
    {
        $this->config = config('cloudinary');
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $this->config['cloud_name'],
                'api_key' => $this->config['api_key'],
                'api_secret' => $this->config['api_secret'],
                'secure' => $this->config['secure']
            ]
        ]);
    }

    /**
     * Upload a single file to Cloudinary
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
            
            // Set upload options
            $uploadOptions = array_merge([
                'folder' => $this->config['folders'][$folder] ?? "au-vlp/{$folder}",
                'public_id' => $filename,
                'resource_type' => $this->getResourceType($file),
                'use_filename' => true,
                'unique_filename' => false,
                'overwrite' => false
            ], $options);

            // Upload to Cloudinary
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getPathname(),
                $uploadOptions
            );

            return [
                'success' => true,
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'],
                'url' => $result['url'],
                'format' => $result['format'],
                'resource_type' => $result['resource_type'],
                'bytes' => $result['bytes'],
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'folder' => $folder
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
     * Delete a file from Cloudinary
     *
     * @param string $publicId
     * @param string $resourceType
     * @return bool
     */
    public function deleteFile(string $publicId, string $resourceType = 'image'): bool
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => $resourceType
            ]);

            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            Log::error('File deletion failed: ' . $e->getMessage(), [
                'public_id' => $publicId,
                'resource_type' => $resourceType
            ]);

            return false;
        }
    }

    /**
     * Generate transformed URL for an image
     *
     * @param string $publicId
     * @param string $transformation
     * @return string
     */
    public function getTransformedUrl(string $publicId, string $transformation = 'medium'): string
    {
        $transformConfig = $this->config['transformations'][$transformation] ?? $this->config['transformations']['medium'];

        return $this->cloudinary->image($publicId)
            ->resize(Resize::fill($transformConfig['width'], $transformConfig['height']))
            ->quality(Quality::auto())
            ->toUrl();
    }

    /**
     * Get multiple transformation URLs
     *
     * @param string $publicId
     * @return array
     */
    public function getAllTransformations(string $publicId): array
    {
        $transformations = [];
        
        foreach ($this->config['transformations'] as $name => $config) {
            $transformations[$name] = $this->getTransformedUrl($publicId, $name);
        }

        return $transformations;
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->config['max_file_size']) {
            throw new \Exception('File size exceeds maximum allowed size');
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedFormats = array_merge(
            $this->config['allowed_formats']['images'],
            $this->config['allowed_formats']['documents'],
            $this->config['allowed_formats']['videos'],
            $this->config['allowed_formats']['audio']
        );

        if (!in_array($extension, $allowedFormats)) {
            throw new \Exception('File format not allowed');
        }

        // Check if file is valid
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
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
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $basename = Str::slug($basename);
        
        return $basename . '_' . time() . '_' . Str::random(8);
    }

    /**
     * Determine Cloudinary resource type based on file
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function getResourceType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $this->config['allowed_formats']['images'])) {
            return 'image';
        } elseif (in_array($extension, $this->config['allowed_formats']['videos'])) {
            return 'video';
        } else {
            return 'raw'; // For documents and other files
        }
    }

    /**
     * Get file category based on extension
     *
     * @param string $extension
     * @return string
     */
    public function getFileCategory(string $extension): string
    {
        $extension = strtolower($extension);
        
        foreach ($this->config['allowed_formats'] as $category => $formats) {
            if (in_array($extension, $formats)) {
                return $category;
            }
        }
        
        return 'unknown';
    }

    /**
     * Upload profile image with specific transformations
     *
     * @param UploadedFile $file
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function uploadProfileImage(UploadedFile $file, int $userId): array
    {
        // Validate that it's an image
        if (!in_array(strtolower($file->getClientOriginalExtension()), $this->config['allowed_formats']['images'])) {
            throw new \Exception('File must be an image');
        }

        $filename = "profile_{$userId}_" . time();
        
        $uploadOptions = [
            'folder' => $this->config['folders']['profiles'],
            'public_id' => $filename,
            'resource_type' => 'image',
            'transformation' => [
                'width' => 400,
                'height' => 400,
                'crop' => 'fill',
                'gravity' => 'face',
                'quality' => 'auto'
            ],
            'overwrite' => true
        ];

        $result = $this->cloudinary->uploadApi()->upload(
            $file->getPathname(),
            $uploadOptions
        );

        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'secure_url' => $result['secure_url'],
            'url' => $result['url'],
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName()
        ];
    }

    /**
     * Upload organization logo with specific transformations
     *
     * @param UploadedFile $file
     * @param int $organizationId
     * @return array
     * @throws \Exception
     */
    public function uploadOrganizationLogo(UploadedFile $file, int $organizationId): array
    {
        // Validate that it's an image
        if (!in_array(strtolower($file->getClientOriginalExtension()), $this->config['allowed_formats']['images'])) {
            throw new \Exception('File must be an image');
        }

        $filename = "org_logo_{$organizationId}_" . time();
        
        $uploadOptions = [
            'folder' => $this->config['folders']['organizations'],
            'public_id' => $filename,
            'resource_type' => 'image',
            'transformation' => [
                'width' => 300,
                'height' => 300,
                'crop' => 'fit',
                'quality' => 'auto',
                'format' => 'auto'
            ],
            'overwrite' => true
        ];

        $result = $this->cloudinary->uploadApi()->upload(
            $file->getPathname(),
            $uploadOptions
        );

        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'secure_url' => $result['secure_url'],
            'url' => $result['url'],
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName()
        ];
    }

    /**
     * Generate thumbnail for existing image
     *
     * @param string $publicId
     * @return string
     */
    public function generateThumbnail(string $publicId): string
    {
        return $this->cloudinary->image($publicId)
            ->resize(Resize::fill(150, 150))
            ->quality(Quality::auto())
            ->toUrl();
    }

    /**
     * Get optimized image URL for different screen sizes
     *
     * @param string $publicId
     * @param string $size
     * @return string
     */
    public function getResponsiveImageUrl(string $publicId, string $size = 'medium'): string
    {
        $transformations = [
            'small' => ['width' => 300, 'height' => 200],
            'medium' => ['width' => 600, 'height' => 400],
            'large' => ['width' => 1200, 'height' => 800],
            'xlarge' => ['width' => 1920, 'height' => 1080]
        ];

        $transform = $transformations[$size] ?? $transformations['medium'];

        return $this->cloudinary->image($publicId)
            ->resize(Resize::limit($transform['width'], $transform['height']))
            ->quality(Quality::auto())
            ->format('auto')
            ->toUrl();
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
        $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js'];
        
        if (in_array($extension, $dangerousExtensions)) {
            return false;
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        $allowedMimes = array_merge(
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ['text/plain', 'text/csv'],
            ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo'],
            ['audio/mpeg', 'audio/wav', 'audio/ogg']
        );

        return in_array($mimeType, $allowedMimes);
    }
}