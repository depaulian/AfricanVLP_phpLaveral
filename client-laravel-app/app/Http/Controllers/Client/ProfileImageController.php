<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileImageUploadRequest;
use App\Models\ProfileImage;
use App\Services\ProfileImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Exception;

class ProfileImageController extends Controller
{
    public function __construct(
        private ProfileImageService $profileImageService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display user's profile images gallery
     */
    public function index(): View
    {
        $user = auth()->user();
        $images = $this->profileImageService->getUserImages($user);
        $stats = $this->profileImageService->getImageStats($user);

        return view('client.profile.images.gallery', compact('images', 'stats'));
    }

    /**
     * Show the image upload form
     */
    public function create(): View
    {
        return view('client.profile.images.upload');
    }

    /**
     * Upload a new profile image
     */
    public function store(ProfileImageUploadRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $file = $request->file('image');
            $cropData = $request->input('crop_data');

            $profileImage = $this->profileImageService->uploadProfileImage($user, $file, $cropData);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully! It will be reviewed before appearing on your profile.',
                'image' => [
                    'id' => $profileImage->id,
                    'thumbnail_url' => $profileImage->thumbnail_url,
                    'profile_url' => $profileImage->profile_url,
                    'status' => $profileImage->status_label,
                    'is_primary' => $profileImage->is_primary
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display a specific image
     */
    public function show(ProfileImage $profileImage): View
    {
        $this->authorize('view', $profileImage);

        return view('client.profile.images.show', compact('profileImage'));
    }

    /**
     * Set image as primary profile image
     */
    public function setPrimary(ProfileImage $profileImage): JsonResponse
    {
        try {
            $this->authorize('update', $profileImage);

            if (!$profileImage->is_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved images can be set as primary.'
                ], 422);
            }

            $this->profileImageService->setPrimaryImage(auth()->user(), $profileImage);

            return response()->json([
                'success' => true,
                'message' => 'Primary image updated successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete a profile image
     */
    public function destroy(ProfileImage $profileImage): JsonResponse
    {
        try {
            $this->authorize('delete', $profileImage);

            $this->profileImageService->deleteImage(auth()->user(), $profileImage);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Download original image
     */
    public function download(ProfileImage $profileImage)
    {
        $this->authorize('view', $profileImage);

        $path = storage_path('app/public/' . $profileImage->original_path);
        
        if (!file_exists($path)) {
            abort(404, 'Image file not found');
        }

        return response()->download($path, $profileImage->filename);
    }

    /**
     * Get image cropping interface
     */
    public function crop(ProfileImage $profileImage): View
    {
        $this->authorize('update', $profileImage);

        return view('client.profile.images.crop', compact('profileImage'));
    }

    /**
     * Update image with new crop data
     */
    public function updateCrop(Request $request, ProfileImage $profileImage): JsonResponse
    {
        try {
            $this->authorize('update', $profileImage);

            $request->validate([
                'crop_data' => 'required|array',
                'crop_data.x' => 'required|numeric|min:0',
                'crop_data.y' => 'required|numeric|min:0',
                'crop_data.width' => 'required|numeric|min:1',
                'crop_data.height' => 'required|numeric|min:1'
            ]);

            $cropData = $request->input('crop_data');

            // Re-process the image with new crop data
            $user = auth()->user();
            
            // Create a temporary file from the original
            $originalPath = storage_path('app/public/' . $profileImage->original_path);
            $tempFile = new \Illuminate\Http\UploadedFile(
                $originalPath,
                $profileImage->filename,
                $profileImage->mime_type,
                null,
                true
            );

            // Delete the old processed images
            \Storage::disk('public')->delete([
                $profileImage->profile_path,
                $profileImage->thumbnail_path
            ]);

            // Create new processed images with updated crop
            $profilePath = $this->createProfileImage($tempFile, $profileImage->filename, $cropData);
            $thumbnailPath = $this->createThumbnailImage($tempFile, $profileImage->filename, $cropData);

            // Update the database record
            $profileImage->update([
                'profile_path' => $profilePath,
                'thumbnail_path' => $thumbnailPath,
                'metadata' => array_merge($profileImage->metadata ?? [], [
                    'crop_data' => $cropData,
                    'updated_at' => now()->toISOString()
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image cropping updated successfully!',
                'image' => [
                    'thumbnail_url' => $profileImage->thumbnail_url,
                    'profile_url' => $profileImage->profile_url
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get user's image statistics
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->profileImageService->getImageStats($user);

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Backup user's images
     */
    public function backup(): JsonResponse
    {
        try {
            $user = auth()->user();
            $results = $this->profileImageService->backupImages($user);

            return response()->json([
                'success' => true,
                'message' => 'Backup process completed.',
                'results' => $results
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to create profile image (duplicated from service for crop update)
     */
    private function createProfileImage($file, string $filename, array $cropData = null): string
    {
        $image = \Intervention\Image\Facades\Image::make($file->getPathname());
        
        if ($cropData) {
            $image->crop(
                (int) $cropData['width'],
                (int) $cropData['height'],
                (int) $cropData['x'],
                (int) $cropData['y']
            );
        }

        $image->resize(800, 800, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $image->encode('jpg', 85);

        $path = 'profile-images/profile/' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        \Storage::disk('public')->put($path, $image->stream());

        return $path;
    }

    /**
     * Helper method to create thumbnail image (duplicated from service for crop update)
     */
    private function createThumbnailImage($file, string $filename, array $cropData = null): string
    {
        $image = \Intervention\Image\Facades\Image::make($file->getPathname());
        
        if ($cropData) {
            $image->crop(
                (int) $cropData['width'],
                (int) $cropData['height'],
                (int) $cropData['x'],
                (int) $cropData['y']
            );
        }

        $image->fit(300, 300);
        $image->encode('jpg', 80);

        $path = 'profile-images/thumbnails/' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        \Storage::disk('public')->put($path, $image->stream());

        return $path;
    }
}