<?php

namespace App\Services;

use App\Models\ProfileImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Exception;

class ProfileImageService
{
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    protected int $maxFileSize = 5242880; // 5MB in bytes
    protected int $maxWidth = 2048;
    protected int $maxHeight = 2048;
    protected int $thumbnailSize = 300;
    protected int $profileSize = 800;

    /**
     * Upload and process profile image
     */
    public function uploadProfileImage(User $user, UploadedFile $file, array $cropData = null): ProfileImage
    {
        $this->validateImage($file);

        // Generate unique filename
        $filename = $this->generateFilename($file);
        
        // Process and store images
        $originalPath = $this->storeOriginalImage($file, $filename);
        $profilePath = $this->createProfileImage($file, $filename, $cropData);
        $thumbnailPath = $this->createThumbnailImage($file, $filename, $cropData);

        // Create database record
        $profileImage = $user->profileImages()->create([
            'filename' => $filename,
            'original_path' => $originalPath,
            'profile_path' => $profilePath,
            'thumbnail_path' => $thumbnailPath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'width' => null, // Will be set after processing
            'height' => null, // Will be set after processing
            'is_primary' => false,
            'is_approved' => false, // Requires moderation
            'metadata' => [
                'crop_data' => $cropData,
                'uploaded_at' => now()->toISOString()
            ]
        ]);

        // Update image dimensions
        $this->updateImageDimensions($profileImage);

        return $profileImage;
    }

    /**
     * Set image as primary profile image
     */
    public function setPrimaryImage(User $user, ProfileImage $image): void
    {
        if ($image->user_id !== $user->id) {
            throw new Exception('Unauthorized access to image');
        }

        // Remove primary status from other images
        $user->profileImages()->update(['is_primary' => false]);
        
        // Set this image as primary
        $image->update(['is_primary' => true]);

        // Update user profile with new image URL
        $user->profile()->updateOrCreate([], [
            'profile_image_url' => Storage::url($image->profile_path)
        ]);
    }

    /**
     * Delete profile image
     */
    public function deleteImage(User $user, ProfileImage $image): void
    {
        if ($image->user_id !== $user->id) {
            throw new Exception('Unauthorized access to image');
        }

        // Delete files from storage
        Storage::disk('public')->delete([
            $image->original_path,
            $image->profile_path,
            $image->thumbnail_path
        ]);

        // If this was the primary image, clear it from profile
        if ($image->is_primary) {
            $user->profile()->update(['profile_image_url' => null]);
        }

        // Delete database record
        $image->delete();
    }

    /**
     * Get user's profile images
     */
    public function getUserImages(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->profileImages()
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Approve image for public display
     */
    public function approveImage(ProfileImage $image, User $moderator): void
    {
        $image->update([
            'is_approved' => true,
            'approved_by' => $moderator->id,
            'approved_at' => now()
        ]);
    }

    /**
     * Reject image with reason
     */
    public function rejectImage(ProfileImage $image, User $moderator, string $reason): void
    {
        $image->update([
            'is_approved' => false,
            'rejected_by' => $moderator->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Validate uploaded image
     */
    protected function validateImage(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }

        // Validate image dimensions
        $imageInfo = getimagesize($file->getPathname());
        if (!$imageInfo) {
            throw new Exception('Invalid image file');
        }

        [$width, $height] = $imageInfo;
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            throw new Exception("Image dimensions too large. Maximum size is {$this->maxWidth}x{$this->maxHeight}px.");
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    /**
     * Store original image
     */
    protected function storeOriginalImage(UploadedFile $file, string $filename): string
    {
        $path = 'profile-images/original/' . $filename;
        Storage::disk('public')->put($path, file_get_contents($file->getPathname()));
        return $path;
    }

    /**
     * Create profile-sized image
     */
    protected function createProfileImage(UploadedFile $file, string $filename, array $cropData = null): string
    {
        $image = Image::make($file->getPathname());
        
        // Apply cropping if provided
        if ($cropData) {
            $image->crop(
                (int) $cropData['width'],
                (int) $cropData['height'],
                (int) $cropData['x'],
                (int) $cropData['y']
            );
        }

        // Resize to profile size while maintaining aspect ratio
        $image->resize($this->profileSize, $this->profileSize, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Optimize image
        $image->encode('jpg', 85);

        $path = 'profile-images/profile/' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        Storage::disk('public')->put($path, $image->stream());

        return $path;
    }

    /**
     * Create thumbnail image
     */
    protected function createThumbnailImage(UploadedFile $file, string $filename, array $cropData = null): string
    {
        $image = Image::make($file->getPathname());
        
        // Apply cropping if provided
        if ($cropData) {
            $image->crop(
                (int) $cropData['width'],
                (int) $cropData['height'],
                (int) $cropData['x'],
                (int) $cropData['y']
            );
        }

        // Create square thumbnail
        $image->fit($this->thumbnailSize, $this->thumbnailSize);

        // Optimize image
        $image->encode('jpg', 80);

        $path = 'profile-images/thumbnails/' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        Storage::disk('public')->put($path, $image->stream());

        return $path;
    }

    /**
     * Update image dimensions in database
     */
    protected function updateImageDimensions(ProfileImage $profileImage): void
    {
        $profilePath = Storage::disk('public')->path($profileImage->profile_path);
        
        if (file_exists($profilePath)) {
            $imageInfo = getimagesize($profilePath);
            if ($imageInfo) {
                $profileImage->update([
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ]);
            }
        }
    }

    /**
     * Get image statistics for user
     */
    public function getImageStats(User $user): array
    {
        $images = $user->profileImages();
        
        return [
            'total_images' => $images->count(),
            'approved_images' => $images->where('is_approved', true)->count(),
            'pending_images' => $images->where('is_approved', false)->whereNull('rejected_at')->count(),
            'rejected_images' => $images->whereNotNull('rejected_at')->count(),
            'primary_image' => $images->where('is_primary', true)->first(),
            'total_storage_used' => $images->sum('file_size')
        ];
    }

    /**
     * Backup images to CDN or external storage
     */
    public function backupImages(User $user): array
    {
        $images = $user->profileImages;
        $backupResults = [];

        foreach ($images as $image) {
            try {
                // This would integrate with your CDN service (AWS S3, Cloudinary, etc.)
                $backupResults[$image->id] = [
                    'success' => true,
                    'backup_url' => null // Would be set by CDN service
                ];
            } catch (Exception $e) {
                $backupResults[$image->id] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $backupResults;
    }
}