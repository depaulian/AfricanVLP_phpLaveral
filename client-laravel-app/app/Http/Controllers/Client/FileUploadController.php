<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ResourceFile;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->middleware('auth');
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display file management interface.
     */
    public function index(Request $request)
    {
        $query = ResourceFile::query();

        // Filter by category (using description field for now)
        if ($request->filled('category')) {
            $query->where('description', 'like', "%{$request->category}%");
        }

        // Filter by file type
        if ($request->filled('type')) {
            if ($request->type === 'images') {
                $query->where('file_category', 'images');
            } elseif ($request->type === 'documents') {
                $query->where('file_category', 'documents');
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $files = $query->orderBy('created', 'desc')
                      ->paginate(20)
                      ->withQueryString();

        // Get categories for filter (using description field)
        $categories = ResourceFile::whereNotNull('description')
                                 ->distinct()
                                 ->pluck('description')
                                 ->filter()
                                 ->take(10);

        // Get file statistics
        $stats = [
            'total_files' => ResourceFile::count(),
            'total_size' => ResourceFile::sum('file_size'),
            'images_count' => ResourceFile::where('file_category', 'images')->count(),
            'documents_count' => ResourceFile::where('file_category', 'documents')->count(),
        ];

        return view('client.files.index', compact('files', 'categories', 'stats'));
    }

    /**
     * Show file upload form.
     */
    public function create()
    {
        return view('client.files.upload');
    }

    /**
     * Handle file upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:' . (config('cloudinary.max_file_size') / 1024),
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        $uploadedFiles = [];
        $errors = [];

        DB::transaction(function () use ($request, &$uploadedFiles, &$errors) {
            foreach ($request->file('files') as $file) {
                try {
                    // Upload file to Cloudinary
                    $uploadResult = $this->fileUploadService->uploadFile($file, 'resources');

                    // Create database record
                    $resourceFile = ResourceFile::create([
                        'filename' => pathinfo($uploadResult['original_filename'], PATHINFO_FILENAME),
                        'original_filename' => $uploadResult['original_filename'],
                        'file_size' => $uploadResult['bytes'],
                        'file_type' => $uploadResult['format'],
                        'file_category' => $this->fileUploadService->getFileCategory($uploadResult['format']),
                        'mime_type' => $uploadResult['mime_type'],
                        'cloudinary_public_id' => $uploadResult['public_id'],
                        'cloudinary_url' => $uploadResult['url'],
                        'cloudinary_secure_url' => $uploadResult['secure_url'],
                        'width' => $uploadResult['width'],
                        'height' => $uploadResult['height'],
                        'status' => 'active',
                        'description' => $request->description
                    ]);

                    $uploadedFiles[] = $resourceFile;

                } catch (\Exception $e) {
                    $errors[] = "Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage();
                }
            }
        });

        if (count($uploadedFiles) > 0) {
            $message = count($uploadedFiles) . ' file(s) uploaded successfully.';
            if (count($errors) > 0) {
                $message .= ' However, some files failed to upload.';
            }
            
            return redirect()->route('files.index')->with('success', $message);
        } else {
            return back()->withErrors($errors)->withInput();
        }
    }

    /**
     * Display the specified file.
     */
    public function show(ResourceFile $file)
    {
        return view('client.files.show', compact('file'));
    }

    /**
     * Download the specified file.
     */
    public function download(ResourceFile $file)
    {
        // Increment download count
        $file->incrementDownloadCount();

        // Redirect to Cloudinary secure URL for download
        return redirect($file->getFileUrl());
    }

    /**
     * Show the form for editing the specified file.
     */
    public function edit(ResourceFile $file)
    {
        return view('client.files.edit', compact('file'));
    }

    /**
     * Update the specified file.
     */
    public function update(Request $request, ResourceFile $file)
    {
        $request->validate([
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        $file->update([
            'description' => $request->description,
            'modified' => now(),
        ]);

        return redirect()->route('files.show', $file)
                        ->with('success', 'File updated successfully.');
    }

    /**
     * Remove the specified file.
     */
    public function destroy(ResourceFile $file)
    {
        $file->delete();

        return redirect()->route('files.index')
                        ->with('success', 'File deleted successfully.');
    }

    /**
     * Upload profile image.
     */
    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $fileInfo = $this->fileUploadService->uploadProfileImage(
                $request->file('image'),
                Auth::id()
            );

            // Update user profile image
            Auth::user()->update([
                'profile_image' => $fileInfo['filename'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile image updated successfully.',
                'image_url' => $fileInfo['url'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload organization logo.
     */
    public function uploadOrganizationLogo(Request $request, $organizationId)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Check if user has permission to update this organization
        $organization = Auth::user()->organizations()
                           ->where('organization_id', $organizationId)
                           ->wherePivot('role', 'admin')
                           ->first();

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this organization.',
            ], 403);
        }

        try {
            $fileInfo = $this->fileUploadService->uploadOrganizationLogo(
                $request->file('logo'),
                $organizationId
            );

            // Update organization logo
            $organization->update([
                'logo' => $fileInfo['filename'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Organization logo updated successfully.',
                'logo_url' => $fileInfo['url'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get file upload progress (for AJAX uploads).
     */
    public function uploadProgress(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        // This would typically integrate with a progress tracking system
        // For now, return a simple response
        return response()->json([
            'progress' => 100,
            'status' => 'complete',
        ]);
    }
}