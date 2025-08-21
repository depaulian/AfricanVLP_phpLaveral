<?php

namespace App\Services;

use App\Models\ForumAttachment;
use App\Models\ForumPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class ForumAttachmentService
{
    /**
     * Allowed file types for forum attachments
     */
    private const ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        
        // Code files
        'text/html',
        'text/css',
        'text/javascript',
        'application/json',
        'application/xml',
    ];

    /**
     * Maximum file size in bytes (10MB)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Maximum number of attachments per post
     */
    private const MAX_ATTACHMENTS_PER_POST = 5;

    /**
     * Upload attachments for a forum post
     *
     * @param ForumPost $post
     * @param array $files
     * @return array
     * @throws Exception
     */
    public function uploadAttachments(ForumPost $post, array $files): array
    {
        $uploadedAttachments = [];
        
        // Check if post already has maximum attachments
        $existingCount = $post->attachments()->count();
        if ($existingCount >= self::MAX_ATTACHMENTS_PER_POST) {
            throw new Exception("Maximum number of attachments (" . self::MAX_ATTACHMENTS_PER_POST . ") already reached for this post.");
        }
        
        // Validate total number of files
        if (count($files) + $existingCount > self::MAX_ATTACHMENTS_PER_POST) {
            throw new Exception("Cannot upload " . count($files) . " files. Maximum " . (self::MAX_ATTACHMENTS_PER_POST - $existingCount) . " files allowed.");
        }

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $attachment = $this->processFileUpload($post, $file);
                $uploadedAttachments[] = $attachment;
            }
        }

        return $uploadedAttachments;
    }

    /**
     * Process individual file upload
     *
     * @param ForumPost $post
     * @param UploadedFile $file
     * @return ForumAttachment
     * @throws Exception
     */
    private function processFileUpload(ForumPost $post, UploadedFile $file): ForumAttachment
    {
        // Validate file
        $this->validateFile($file);
        
        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = $this->generateUniqueFilename($originalName, $extension);
        
        // Define storage path
        $storagePath = 'forum-attachments/' . date('Y/m/d') . '/' . $post->id;
        
        // Store file
        $filePath = $file->storeAs($storagePath, $filename, 'private');
        
        if (!$filePath) {
            throw new Exception("Failed to store file: {$originalName}");
        }

        // Create attachment record
        $attachment = ForumAttachment::create([
            'post_id' => $post->id,
            'file_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'download_count' => 0,
        ]);

        return $attachment;
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new Exception("File size exceeds maximum allowed size of " . $this->formatBytes(self::MAX_FILE_SIZE) . ". File: {$file->getClientOriginalName()}");
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new Exception("File type not allowed: {$mimeType}. File: {$file->getClientOriginalName()}");
        }

        // Additional security checks
        $this->performSecurityChecks($file);
    }

    /**
     * Perform additional security checks on the file
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    private function performSecurityChecks(UploadedFile $file): void
    {
        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Check for dangerous extensions
        $dangerousExtensions = [
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
            'php', 'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh'
        ];
        
        if (in_array($extension, $dangerousExtensions)) {
            throw new Exception("File extension not allowed for security reasons: {$extension}");
        }

        // Check for double extensions
        if (substr_count($filename, '.') > 1) {
            $parts = explode('.', $filename);
            if (count($parts) > 2) {
                $secondLastExtension = strtolower($parts[count($parts) - 2]);
                if (in_array($secondLastExtension, $dangerousExtensions)) {
                    throw new Exception("Double extension detected with dangerous file type: {$filename}");
                }
            }
        }

        // Basic content validation for images
        if (strpos($file->getMimeType(), 'image/') === 0) {
            $imageInfo = @getimagesize($file->getPathname());
            if ($imageInfo === false) {
                throw new Exception("Invalid image file: {$filename}");
            }
        }
    }

    /**
     * Generate unique filename
     *
     * @param string $originalName
     * @param string $extension
     * @return string
     */
    private function generateUniqueFilename(string $originalName, string $extension): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = Str::slug($baseName);
        $uniqueId = Str::random(8);
        
        return $safeName . '_' . $uniqueId . '.' . $extension;
    }

    /**
     * Download attachment and track download count
     *
     * @param ForumAttachment $attachment
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws Exception
     */
    public function downloadAttachment(ForumAttachment $attachment)
    {
        // Check if file exists
        if (!Storage::disk('private')->exists($attachment->file_path)) {
            throw new Exception("File not found: {$attachment->file_name}");
        }

        // Increment download count
        $attachment->increment('download_count');

        // Return file download response
        return Storage::disk('private')->download(
            $attachment->file_path,
            $attachment->file_name,
            [
                'Content-Type' => $attachment->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $attachment->file_name . '"'
            ]
        );
    }

    /**
     * Delete attachment and its file
     *
     * @param ForumAttachment $attachment
     * @return bool
     */
    public function deleteAttachment(ForumAttachment $attachment): bool
    {
        try {
            // Delete file from storage
            if (Storage::disk('private')->exists($attachment->file_path)) {
                Storage::disk('private')->delete($attachment->file_path);
            }

            // Delete database record
            return $attachment->delete();
        } catch (Exception $e) {
            // Log error but don't throw exception
            logger()->error("Failed to delete attachment: " . $e->getMessage(), [
                'attachment_id' => $attachment->id,
                'file_path' => $attachment->file_path
            ]);
            
            return false;
        }
    }

    /**
     * Get file icon class based on MIME type
     *
     * @param string $mimeType
     * @return string
     */
    public function getFileIcon(string $mimeType): string
    {
        $iconMap = [
            // Images
            'image/jpeg' => 'fas fa-image',
            'image/png' => 'fas fa-image',
            'image/gif' => 'fas fa-image',
            'image/webp' => 'fas fa-image',
            'image/svg+xml' => 'fas fa-image',
            
            // Documents
            'application/pdf' => 'fas fa-file-pdf',
            'application/msword' => 'fas fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fas fa-file-word',
            'application/vnd.ms-excel' => 'fas fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fas fa-file-excel',
            'application/vnd.ms-powerpoint' => 'fas fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fas fa-file-powerpoint',
            'text/plain' => 'fas fa-file-alt',
            'text/csv' => 'fas fa-file-csv',
            
            // Archives
            'application/zip' => 'fas fa-file-archive',
            'application/x-rar-compressed' => 'fas fa-file-archive',
            'application/x-7z-compressed' => 'fas fa-file-archive',
            
            // Code files
            'text/html' => 'fas fa-file-code',
            'text/css' => 'fas fa-file-code',
            'text/javascript' => 'fas fa-file-code',
            'application/json' => 'fas fa-file-code',
            'application/xml' => 'fas fa-file-code',
        ];

        return $iconMap[$mimeType] ?? 'fas fa-file';
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get allowed file types for display
     *
     * @return array
     */
    public function getAllowedFileTypes(): array
    {
        return [
            'Images' => ['JPEG', 'PNG', 'GIF', 'WebP', 'SVG'],
            'Documents' => ['PDF', 'Word', 'Excel', 'PowerPoint', 'Text', 'CSV'],
            'Archives' => ['ZIP', 'RAR', '7Z'],
            'Code' => ['HTML', 'CSS', 'JavaScript', 'JSON', 'XML']
        ];
    }

    /**
     * Get maximum file size in human readable format
     *
     * @return string
     */
    public function getMaxFileSize(): string
    {
        return $this->formatBytes(self::MAX_FILE_SIZE);
    }

    /**
     * Get maximum number of attachments per post
     *
     * @return int
     */
    public function getMaxAttachmentsPerPost(): int
    {
        return self::MAX_ATTACHMENTS_PER_POST;
    }

    /**
     * Validate attachment access for user
     *
     * @param ForumAttachment $attachment
     * @param \App\Models\User $user
     * @return bool
     */
    public function canUserAccessAttachment(ForumAttachment $attachment, $user): bool
    {
        $post = $attachment->post;
        $thread = $post->thread;
        $forum = $thread->forum;

        // Check if user can view the forum
        if ($forum->is_private && $forum->organization) {
            return $forum->organization->users()->where('users.id', $user->id)->exists();
        }

        return true; // Public forum
    }
}