<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfileImageService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileImageController extends Controller
{
    protected ProfileImageService $profileImageService;
    protected ActivityLogService $activityLogService;

    public function __construct(
        ProfileImageService $profileImageService,
        ActivityLogService $activityLogService
    ) {
        $this->profileImageService = $profileImageService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display profile image management dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = User::whereNotNull('profile_image')
            ->with(['organizationMemberships.organization'])
            ->orderBy('modified', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('has_image')) {
            if ($request->has_image === 'yes') {
                $query->whereNotNull('profile_image');
            } else {
                $query->whereNull('profile_image');
            }
        }

        $users = $query->paginate(20);

        // Get statistics
        $stats = $this->getImageStats();

        if ($request->expectsJson()) {
            return response()->json([
                'users' => $users,
                'stats' => $stats,
            ]);
        }

        return view('admin.profile-images.index', compact('users', 'stats'));
    }

    /**
     * Show user profile image management
     */
    public function show(User $user): View|JsonResponse
    {
        $imageUrls = $this->profileImageService->getProfileImageUrls($user);
        $metadata = $this->profileImageService->getImageMetadata($user);

        if (request()->expectsJson()) {
            return response()->json([
                'user' => $user,
                'image_urls' => $imageUrls,
                'metadata' => $metadata,
            ]);
        }

        return view('admin.profile-images.show', compact('user', 'imageUrls', 'metadata'));
    }

    /**
     * Upload profile image for user
     */
    public function upload(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $result = $this->profileImageService->uploadProfileImage($user, $request->file('image'));

        if ($result['success']) {
            // Log the upload
            $this->activityLogService->log(
                'update',
                $user,
                auth()->user(),
                "Uploaded profile image for user: {$user->name}"
            );

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('admin.profile-images.show', $user)
                            ->with('success', $result['message']);
        } else {
            if ($request->expectsJson()) {
                return response()->json($result, 400);
            }

            return back()->with('error', $result['message']);
        }
    }

    /**
     * Crop profile image
     */
    public function crop(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'x' => 'required|numeric|min:0',
            'y' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:100',
            'height' => 'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $cropData = $request->only(['x', 'y', 'width', 'height']);
        $result = $this->profileImageService->cropProfileImage($user, $cropData);

        if ($result['success']) {
            // Log the crop
            $this->activityLogService->log(
                'update',
                $user,
                auth()->user(),
                "Cropped profile image for user: {$user->name}"
            );

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('admin.profile-images.show', $user)
                            ->with('success', $result['message']);
        } else {
            if ($request->expectsJson()) {
                return response()->json($result, 400);
            }

            return back()->with('error', $result['message']);
        }
    }

    /**
     * Delete profile image
     */
    public function delete(User $user): JsonResponse|RedirectResponse
    {
        $result = $this->profileImageService->deleteProfileImage($user);

        if ($result['success']) {
            // Log the deletion
            $this->activityLogService->log(
                'delete',
                $user,
                auth()->user(),
                "Deleted profile image for user: {$user->name}"
            );

            if (request()->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('admin.profile-images.show', $user)
                            ->with('success', $result['message']);
        } else {
            if (request()->expectsJson()) {
                return response()->json($result, 400);
            }

            return back()->with('error', $result['message']);
        }
    }

    /**
     * Bulk delete profile images
     */
    public function bulkDelete(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        $users = User::whereIn('id', $request->user_ids)->get();
        $deleted = 0;
        $errors = [];

        foreach ($users as $user) {
            $result = $this->profileImageService->deleteProfileImage($user);
            
            if ($result['success']) {
                $this->activityLogService->log(
                    'delete',
                    $user,
                    auth()->user(),
                    "Bulk deleted profile image for user: {$user->name}"
                );
                $deleted++;
            } else {
                $errors[] = "Failed to delete image for {$user->name}: {$result['message']}";
            }
        }

        $message = "Successfully deleted {$deleted} profile images";
        if (!empty($errors)) {
            $message .= ". Errors: " . implode(', ', $errors);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'deleted' => $deleted,
                'errors' => $errors,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Get profile image statistics
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->getImageStats());
    }

    /**
     * Cleanup orphaned images
     */
    public function cleanup(): JsonResponse|RedirectResponse
    {
        $result = $this->profileImageService->cleanupOrphanedImages();

        // Log the cleanup
        $this->activityLogService->log(
            'system',
            null,
            auth()->user(),
            "Performed profile image cleanup: {$result['cleaned']} directories cleaned"
        );

        if (request()->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('success', "Cleanup completed. {$result['cleaned']} orphaned directories removed.");
        } else {
            return back()->with('error', $result['message']);
        }
    }

    /**
     * Download user profile image
     */
    public function download(User $user, string $size = 'large')
    {
        $imagePath = match($size) {
            'thumbnail' => $user->profile_image_thumbnail,
            'medium' => $user->profile_image_medium,
            'large' => $user->profile_image,
            default => $user->profile_image,
        };

        if (!$imagePath || !Storage::disk('public')->exists($imagePath)) {
            abort(404, 'Image not found');
        }

        // Log download
        $this->activityLogService->log(
            'download',
            $user,
            auth()->user(),
            "Downloaded profile image ({$size}) for user: {$user->name}"
        );

        $filename = $user->name . '_profile_' . $size . '.' . pathinfo($imagePath, PATHINFO_EXTENSION);
        return Storage::disk('public')->download($imagePath, $filename);
    }

    /**
     * Export profile image data as CSV
     */
    public function export(Request $request)
    {
        $query = User::whereNotNull('profile_image')
            ->with(['organizationMemberships.organization']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="profile_images_export_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'User ID', 'Name', 'Email', 'Profile Image URL', 'Thumbnail URL',
                'Medium URL', 'Upload Date', 'Organizations'
            ]);

            // CSV data
            foreach ($users as $user) {
                $imageUrls = $this->profileImageService->getProfileImageUrls($user);
                $organizations = $user->organizationMemberships->pluck('organization.name')->implode(', ');

                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $imageUrls['large'] ?? '',
                    $imageUrls['thumbnail'] ?? '',
                    $imageUrls['medium'] ?? '',
                    $user->modified->format('Y-m-d H:i:s'),
                    $organizations,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get image statistics for dashboard
     */
    private function getImageStats(): array
    {
        $totalUsers = User::count();
        $usersWithImages = User::whereNotNull('profile_image')->count();
        $usersWithoutImages = $totalUsers - $usersWithImages;
        
        // Calculate storage usage
        $totalSize = 0;
        $users = User::whereNotNull('profile_image')->get();
        
        foreach ($users as $user) {
            $paths = [
                $user->profile_image,
                $user->profile_image_thumbnail,
                $user->profile_image_medium,
            ];
            
            foreach ($paths as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    $totalSize += Storage::disk('public')->size($path);
                }
            }
        }

        // Recent uploads (last 7 days)
        $recentUploads = User::whereNotNull('profile_image')
            ->where('modified', '>=', now()->subDays(7))
            ->count();

        return [
            'total_users' => $totalUsers,
            'users_with_images' => $usersWithImages,
            'users_without_images' => $usersWithoutImages,
            'coverage_percentage' => $totalUsers > 0 ? round(($usersWithImages / $totalUsers) * 100, 2) : 0,
            'total_storage_size' => $totalSize,
            'formatted_storage_size' => $this->formatBytes($totalSize),
            'recent_uploads' => $recentUploads,
            'average_size_per_user' => $usersWithImages > 0 ? round($totalSize / $usersWithImages) : 0,
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
