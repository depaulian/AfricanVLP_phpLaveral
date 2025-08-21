<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Models\Resource;
use App\Models\ResourceFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload a single file
     */
    public function uploadSingle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:' . (config('cloudinary.max_file_size') / 1024),
            'folder' => 'sometimes|string|in:resources,profiles,organizations,events,news,blog',
            'resource_id' => 'sometimes|exists:resources,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $folder = $request->input('folder', 'resources');
            
            // Upload to Cloudinary
            $uploadResult = $this->fileUploadService->uploadFile($file, $folder);

            $response = [
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $uploadResult
            ];

            // If resource_id is provided, create ResourceFile record
            if ($request->has('resource_id')) {
                $resourceFile = $this->createResourceFileRecord($request->input('resource_id'), $uploadResult);
                $response['data']['resource_file'] = $resourceFile;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:' . (config('cloudinary.max_file_size') / 1024),
            'folder' => 'sometimes|string|in:resources,profiles,organizations,events,news,blog',
            'resource_id' => 'sometimes|exists:resources,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $files = $request->file('files');
            $folder = $request->input('folder', 'resources');
            
            // Upload to Cloudinary
            $uploadResult = $this->fileUploadService->uploadMultipleFiles($files, $folder);

            $response = [
                'success' => $uploadResult['success'],
                'message' => "Uploaded {$uploadResult['total_uploaded']} files successfully",
                'data' => $uploadResult
            ];

            // If resource_id is provided, create ResourceFile records
            if ($request->has('resource_id') && !empty($uploadResult['uploaded'])) {
                $resourceFiles = [];
                foreach ($uploadResult['uploaded'] as $uploadedFile) {
                    $resourceFiles[] = $this->createResourceFileRecord($request->input('resource_id'), $uploadedFile);
                }
                $response['data']['resource_files'] = $resourceFiles;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Multiple file upload error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a file
     */
    public function deleteFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'public_id' => 'required|string',
            'resource_type' => 'sometimes|string|in:image,video,raw',
            'resource_file_id' => 'sometimes|exists:resource_files,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $publicId = $request->input('public_id');
            $resourceType = $request->input('resource_type', 'image');
            
            // Delete from Cloudinary
            $deleted = $this->fileUploadService->deleteFile($publicId, $resourceType);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete file from Cloudinary'
                ], 500);
            }

            // Delete ResourceFile record if provided
            if ($request->has('resource_file_id')) {
                $resourceFile = ResourceFile::find($request->input('resource_file_id'));
                if ($resourceFile) {
                    $resourceFile->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('File deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'File deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transformed image URLs
     */
    public function getTransformations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'public_id' => 'required|string',
            'transformation' => 'sometimes|string|in:thumbnail,medium,large'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $publicId = $request->input('public_id');
            
            if ($request->has('transformation')) {
                $transformation = $request->input('transformation');
                $url = $this->fileUploadService->getTransformedUrl($publicId, $transformation);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'transformation' => $transformation,
                        'url' => $url
                    ]
                ]);
            } else {
                $transformations = $this->fileUploadService->getAllTransformations($publicId);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'transformations' => $transformations
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Get transformations error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transformations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show file details
     */
    public function show(ResourceFile $file): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $file->load('resource')
        ]);
    }

    /**
     * Update file details
     */
    public function update(Request $request, ResourceFile $file): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string|max:500',
            'resource_id' => 'nullable|exists:resources,id',
            'status' => 'sometimes|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file->update([
                'description' => $request->input('description'),
                'resource_id' => $request->input('resource_id'),
                'status' => $request->input('status', $file->status),
                'modified' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File updated successfully',
                'data' => $file->load('resource')
            ]);

        } catch (\Exception $e) {
            Log::error('File update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'File update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file
     */
    public function destroy(ResourceFile $file): JsonResponse
    {
        try {
            $file->delete(); // This will also delete from Cloudinary via the model's delete method

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('File deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'File deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create ResourceFile record
     */
    protected function createResourceFileRecord(int $resourceId, array $uploadResult): ResourceFile
    {
        return ResourceFile::create([
            'resource_id' => $resourceId,
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
            'status' => 'active'
        ]);
    }
}