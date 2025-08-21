<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ProfileImageService
{
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    protected array $imageSizes = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 600, 'height' => 600],
    ];

    protected int $maxFileSize = 5120; // 5MB in KB
    protected string $disk = 'public';
    protected string $basePath = 'profile-images';

    /**
     * Upload and process profile image
     */
    public function uploadProfileImage(User $user, UploadedFile $file): array
    {
        try {
            // Validate file
            $this->validateImage($file);

            // Generate unique filename
            $filename = $this->generateFilename($file);
            
            // Create directory structure
            $userPath = $this->getUserPath($user->id);
            
            // Delete existing images
            $this->deleteExistingImages($user);

            // Process and save images in different sizes
            $imagePaths = $this->processAndSaveImages($file, $userPath, $filename);

            // Update user profile
            $user->update([
                'profile_image' => $imagePaths['large'],
                'profile_image_thumbnail' => $imagePaths['thumbnail'],
                'profile_image_medium' => $imagePaths['medium'],
            ]);

            Log::info("Profile image uploaded successfully for user {$user->id}");

            return [
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'images' => $imagePaths,
                'urls' => [
                    'thumbnail' => Storage::disk($this->disk)->url($imagePaths['thumbnail']),
                    'medium' => Storage::disk($this->disk)->url($imagePaths['medium']),
                    'large' => Storage::disk($this->disk)->url($imagePaths['large']),
                ],
            ];

        } catch (\Exception $e) {
            Log::error("Profile image upload failed for user {$user->id}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete profile image
     */
    public function deleteProfileImage(User $user): array
    {
        try {
            $this->deleteExistingImages($user);

            $user->update([
                'profile_image' => null,
                'profile_image_thumbnail' => null,
                'profile_image_medium' => null,
            ]);

            Log::info("Profile image deleted successfully for user {$user->id}");

            return [
                'success' => true,
                'message' => 'Profile image deleted successfully',
            ];

        } catch (\Exception $e) {
            Log::error("Profile image deletion failed for user {$user->id}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to delete profile image',
            ];
        }
    }

    /**
     * Get profile image URLs
     */
    public function getProfileImageUrls(User $user): array
    {
        $urls = [
            'thumbnail' => null,
            'medium' => null,
            'large' => null,
        ];

        if ($user->profile_image_thumbnail) {
            $urls['thumbnail'] = Storage::disk($this->disk)->url($user->profile_image_thumbnail);
        }

        if ($user->profile_image_medium) {
            $urls['medium'] = Storage::disk($this->disk)->url($user->profile_image_medium);
        }

        if ($user->profile_image) {
            $urls['large'] = Storage::disk($this->disk)->url($user->profile_image);
        }

        return $urls;
    }

    /**
     * Validate uploaded image
     */
    protected function validateImage(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > ($this->maxFileSize * 1024)) {
            throw new \InvalidArgumentException("File size must be less than {$this->maxFileSize}KB");
        }

        // Check mime type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed');
        }

        // Check if file is actually an image
        $imageInfo = getimagesize($file->getPathname());
        if (!$imageInfo) {
            throw new \InvalidArgumentException('Invalid image file');
        }

        // Check image dimensions (minimum requirements)
        if ($imageInfo[0] < 100 || $imageInfo[1] < 100) {
            throw new \InvalidArgumentException('Image must be at least 100x100 pixels');
        }

        // Check image dimensions (maximum requirements)
        if ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
            throw new \InvalidArgumentException('Image must be less than 4000x4000 pixels');
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
     * Get user-specific path
     */
    protected function getUserPath(int $userId): string
    {
        return $this->basePath . '/' . $userId;
    }

    /**
     * Delete existing images for user
     */
    protected function deleteExistingImages(User $user): void
    {
        $imagePaths = [
            $user->profile_image,
            $user->profile_image_thumbnail,
            $user->profile_image_medium,
        ];

        foreach ($imagePaths as $path) {
            if ($path && Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }
        }
    }

    /**
     * Process and save images in different sizes
     */
    protected function processAndSaveImages(UploadedFile $file, string $userPath, string $filename): array
    {
        $imagePaths = [];
        $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        foreach ($this->imageSizes as $size => $dimensions) {
            $sizeFilename = $baseFilename . '_' . $size . '.' . $extension;
            $fullPath = $userPath . '/' . $sizeFilename;

            // Create and resize image
            $image = Image::make($file->getPathname());
            
            // Resize image maintaining aspect ratio
            $image->fit($dimensions['width'], $dimensions['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Optimize image quality
            $quality = $this->getQualityForSize($size);
            
            // Save image to storage
            $imageContent = $image->encode($extension, $quality)->getEncoded();
            Storage::disk($this->disk)->put($fullPath, $imageContent);

            $imagePaths[$size] = $fullPath;
        }

        return $imagePaths;
    }

    /**
     * Get image quality based on size
     */
    protected function getQualityForSize(string $size): int
    {
        return match($size) {
            'thumbnail' => 70,
            'medium' => 80,
            'large' => 90,
            default => 80,
        };
    }

    /**
     * Crop image to specific dimensions
     */
    public function cropProfileImage(User $user, array $cropData): array
    {
        try {
            if (!$user->profile_image) {
                throw new \InvalidArgumentException('No profile image to crop');
            }

            $originalPath = $user->profile_image;
            if (!Storage::disk($this->disk)->exists($originalPath)) {
                throw new \InvalidArgumentException('Original image file not found');
            }

            // Validate crop data
            $this->validateCropData($cropData);

            // Get original image
            $imageContent = Storage::disk($this->disk)->get($originalPath);
            $image = Image::make($imageContent);

            // Apply crop
            $image->crop(
                (int)$cropData['width'],
                (int)$cropData['height'],
                (int)$cropData['x'],
                (int)$cropData['y']
            );

            // Generate new filename
            $filename = $this->generateFilename(new \SplFileInfo($originalPath));
            $userPath = $this->getUserPath($user->id);

            // Delete existing images
            $this->deleteExistingImages($user);

            // Save cropped image in different sizes
            $imagePaths = $this->saveResizedImages($image, $userPath, $filename);

            // Update user profile
            $user->update([
                'profile_image' => $imagePaths['large'],
                'profile_image_thumbnail' => $imagePaths['thumbnail'],
                'profile_image_medium' => $imagePaths['medium'],
            ]);

            Log::info("Profile image cropped successfully for user {$user->id}");

            return [
                'success' => true,
                'message' => 'Profile image cropped successfully',
                'images' => $imagePaths,
                'urls' => [
                    'thumbnail' => Storage::disk($this->disk)->url($imagePaths['thumbnail']),
                    'medium' => Storage::disk($this->disk)->url($imagePaths['medium']),
                    'large' => Storage::disk($this->disk)->url($imagePaths['large']),
                ],
            ];

        } catch (\Exception $e) {
            Log::error("Profile image crop failed for user {$user->id}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate crop data
     */
    protected function validateCropData(array $cropData): void
    {
        $required = ['x', 'y', 'width', 'height'];
        
        foreach ($required as $field) {
            if (!isset($cropData[$field]) || !is_numeric($cropData[$field])) {
                throw new \InvalidArgumentException("Invalid crop data: {$field} is required and must be numeric");
            }
        }

        if ($cropData['width'] < 100 || $cropData['height'] < 100) {
            throw new \InvalidArgumentException('Crop area must be at least 100x100 pixels');
        }
    }

    /**
     * Save resized images from existing image object
     */
    protected function saveResizedImages($image, string $userPath, string $filename): array
    {
        $imagePaths = [];
        $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        foreach ($this->imageSizes as $size => $dimensions) {
            $sizeFilename = $baseFilename . '_' . $size . '.' . $extension;
            $fullPath = $userPath . '/' . $sizeFilename;

            // Clone image and resize
            $resizedImage = clone $image;
            $resizedImage->fit($dimensions['width'], $dimensions['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Optimize image quality
            $quality = $this->getQualityForSize($size);
            
            // Save image to storage
            $imageContent = $resizedImage->encode($extension, $quality)->getEncoded();
            Storage::disk($this->disk)->put($fullPath, $imageContent);

            $imagePaths[$size] = $fullPath;
        }

        return $imagePaths;
    }

    /**
     * Get image metadata
     */
    public function getImageMetadata(User $user): ?array
    {
        if (!$user->profile_image || !Storage::disk($this->disk)->exists($user->profile_image)) {
            return null;
        }

        $imageContent = Storage::disk($this->disk)->get($user->profile_image);
        $image = Image::make($imageContent);

        return [
            'width' => $image->width(),
            'height' => $image->height(),
            'size' => Storage::disk($this->disk)->size($user->profile_image),
            'mime_type' => $image->mime(),
            'url' => Storage::disk($this->disk)->url($user->profile_image),
        ];
    }

    /**
     * Cleanup orphaned images (for maintenance)
     */
    public function cleanupOrphanedImages(): array
    {
        $cleaned = 0;
        $errors = [];

        try {
            $directories = Storage::disk($this->disk)->directories($this->basePath);
            
            foreach ($directories as $directory) {
                $userId = basename($directory);
                
                // Check if user exists
                if (!User::find($userId)) {
                    // Delete orphaned directory
                    Storage::disk($this->disk)->deleteDirectory($directory);
                    $cleaned++;
                }
            }

            Log::info("Cleaned up {$cleaned} orphaned profile image directories");

            return [
                'success' => true,
                'cleaned' => $cleaned,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            Log::error("Profile image cleanup failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'cleaned' => $cleaned,
                'errors' => $errors,
            ];
        }
    }
}
