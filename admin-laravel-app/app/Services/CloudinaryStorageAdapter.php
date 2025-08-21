<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use App\Services\CloudinaryService;
use Exception;

class CloudinaryStorageAdapter
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * Store a file using Cloudinary based on context
     */
    public function store(UploadedFile $file, string $context, array $metadata = []): array
    {
        try {
            switch ($context) {
                case 'profile_image':
                    return $this->storeProfileImage($file, $metadata['user_id']);
                
                case 'organization_image':
                    return $this->storeOrganizationImage($file, $metadata['organization_id']);
                
                case 'event_image':
                    return $this->storeEventImage($file, $metadata['event_id']);
                
                case 'forum_attachment':
                    return $this->storeForumAttachment($file, $metadata['thread_id']);
                
                case 'support_attachment':
                    return $this->storeSupportAttachment($file, $metadata['ticket_id']);
                
                case 'feedback_attachment':
                    return $this->storeFeedbackAttachment($file, $metadata['feedback_id']);
                
                case 'resource_file':
                    return $this->storeResource($file, $metadata['resource_id']);
                
                case 'general_file':
                    return $this->storeGeneralFile($file, $metadata);
                
                case 'general_image':
                    return $this->storeGeneralImage($file, $metadata);
                
                default:
                    throw new Exception("Unknown storage context: {$context}");
            }
        } catch (Exception $e) {
            Log::error("CloudinaryStorageAdapter failed for context {$context}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store profile image with multiple sizes
     */
    protected function storeProfileImage(UploadedFile $file, string $userId): array
    {
        $result = $this->cloudinaryService->uploadProfileImage($file, $userId);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'original_url' => $result['original_url'],
            'thumbnail_url' => $result['thumbnail_url'],
            'medium_url' => $result['medium_url'],
            'large_url' => $result['large_url'],
            'file_size' => $result['file_size'],
            'format' => $result['format'],
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Store organization image
     */
    protected function storeOrganizationImage(UploadedFile $file, string $organizationId): array
    {
        $result = $this->cloudinaryService->uploadOrganizationImage($file, $organizationId);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'file_size' => $result['bytes'],
            'format' => $result['format'],
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Store event image
     */
    protected function storeEventImage(UploadedFile $file, string $eventId): array
    {
        $result = $this->cloudinaryService->uploadEventImage($file, $eventId);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'file_size' => $result['bytes'],
            'format' => $result['format'],
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Store forum attachment
     */
    protected function storeForumAttachment(UploadedFile $file, string $threadId): array
    {
        $result = $this->cloudinaryService->uploadForumAttachment($file, $threadId);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'file_size' => $result['bytes'],
            'format' => $result['format'],
            'original_filename' => $file->getClientOriginalName(),
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Store support ticket attachment
     */
    protected function storeSupportAttachment(UploadedFile $file, string $ticketId): array
    {
        $result = $this->cloudinaryService->uploadSupportAttachment($file, $ticketId);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'file_size' => $result['bytes'],
            'format' => $result['format'],
            'original_filename' => $file->getClientOriginalName(),
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Store feedback attachment
     */
    protected function storeFeedbackAttachment(UploadedFile $file, string $feedbackId): array
    {
        $result = $this->cloudinaryService->uploadFeedbackAttachment($file, $feedbackId);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'file_size' => $result['bytes'],
            'format' => $result['format'],
            'original_filename' => $file->getClientOriginalName(),
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Store resource file
     */
    protected function storeResource(UploadedFile $file, string $resourceId): array
    {
        $result = $this->cloudinaryService->uploadResource($file, $resourceId);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'file_size' => $result['bytes'],
            'format' => $result['format'],
            'original_filename' => $file->getClientOriginalName(),
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Store general file
     */
    protected function storeGeneralFile(UploadedFile $file, array $metadata): array
    {
        $folder = $metadata['folder'] ?? 'africavlp/general';
        $result = $this->cloudinaryService->uploadFile($file, ['folder' => $folder]);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'file_size' => $result['bytes'],
            'format' => $result['format'],
            'original_filename' => $file->getClientOriginalName(),
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Store general image
     */
    protected function storeGeneralImage(UploadedFile $file, array $metadata): array
    {
        $folder = $metadata['folder'] ?? 'africavlp/images';
        $result = $this->cloudinaryService->uploadImage($file, ['folder' => $folder]);
        
        return [
            'success' => true,
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'file_size' => $result['bytes'],
            'format' => $result['format'],
            'original_filename' => $file->getClientOriginalName(),
            'storage_type' => 'cloudinary',
        ];
    }

    /**
     * Delete a file from Cloudinary
     */
    public function delete(string $publicId): bool
    {
        try {
            return $this->cloudinaryService->deleteFile($publicId);
        } catch (Exception $e) {
            Log::error("CloudinaryStorageAdapter delete failed for {$publicId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file information
     */
    public function getFileInfo(string $publicId): ?array
    {
        try {
            return $this->cloudinaryService->getFileInfo($publicId);
        } catch (Exception $e) {
            Log::error("CloudinaryStorageAdapter getFileInfo failed for {$publicId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate transformation URL
     */
    public function generateUrl(string $publicId, array $transformations = []): string
    {
        return $this->cloudinaryService->generateTransformationUrl($publicId, $transformations);
    }

    /**
     * Validate file before upload
     */
    public function validateFile(UploadedFile $file, string $context): array
    {
        $errors = [];
        
        // Basic file validation
        if (!$file->isValid()) {
            $errors[] = 'File upload failed';
            return ['valid' => false, 'errors' => $errors];
        }

        // Context-specific validation
        switch ($context) {
            case 'profile_image':
            case 'organization_image':
            case 'event_image':
            case 'general_image':
                $errors = array_merge($errors, $this->validateImage($file));
                break;
                
            case 'forum_attachment':
            case 'support_attachment':
            case 'feedback_attachment':
            case 'resource_file':
            case 'general_file':
                $errors = array_merge($errors, $this->validateGeneralFile($file));
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate image files
     */
    protected function validateImage(UploadedFile $file): array
    {
        $errors = [];
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP';
        }
        
        // Check file size (10MB max)
        if ($file->getSize() > 10 * 1024 * 1024) {
            $errors[] = 'Image size must be less than 10MB';
        }
        
        // Check image dimensions
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            if ($width > 4000 || $height > 4000) {
                $errors[] = 'Image dimensions must be less than 4000x4000 pixels';
            }
        }
        
        return $errors;
    }

    /**
     * Validate general files
     */
    protected function validateGeneralFile(UploadedFile $file): array
    {
        $errors = [];
        
        // Check file size (50MB max)
        if ($file->getSize() > 50 * 1024 * 1024) {
            $errors[] = 'File size must be less than 50MB';
        }
        
        // Check for dangerous file types
        $dangerousTypes = [
            'application/x-executable',
            'application/x-msdownload',
            'application/x-msdos-program',
            'application/x-winexe',
        ];
        
        if (in_array($file->getMimeType(), $dangerousTypes)) {
            $errors[] = 'Executable files are not allowed';
        }
        
        return $errors;
    }
}
