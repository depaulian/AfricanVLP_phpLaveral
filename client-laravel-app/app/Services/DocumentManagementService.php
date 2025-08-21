<?php

namespace App\Services;

use App\Models\UserDocument;
use App\Models\User;
use App\Notifications\DocumentExpirationReminder;
use App\Notifications\DocumentVerificationStatusChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class DocumentManagementService
{
    /**
     * Upload a document with virus scanning and validation.
     */
    public function uploadDocument(User $user, UploadedFile $file, string $category, array $metadata = []): UserDocument
    {
        // Validate file
        $this->validateFile($file);
        
        // Perform virus scan
        $scanResult = $this->performVirusScan($file);
        
        if (!$scanResult['safe']) {
            throw new Exception('File failed security scan: ' . $scanResult['reason']);
        }
        
        // Store file securely
        $filePath = $this->storeFileSecurely($file, $user->id);
        
        // Create document record
        $document = $user->documents()->create([
            'name' => $metadata['name'] ?? $file->getClientOriginalName(),
            'category' => $category,
            'description' => $metadata['description'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'verification_status' => 'pending',
            'expiry_date' => $metadata['expiry_date'] ?? null,
            'is_sensitive' => $metadata['is_sensitive'] ?? false,
            'metadata' => [
                'scan_result' => $scanResult,
                'upload_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'original_name' => $file->getClientOriginalName(),
                'hash' => hash_file('sha256', $file->getRealPath())
            ]
        ]);
        
        // Schedule expiration reminders if applicable
        if ($document->expiry_date) {
            $this->scheduleExpirationReminders($document);
        }
        
        // Log document upload
        Log::info('Document uploaded', [
            'document_id' => $document->id,
            'user_id' => $user->id,
            'category' => $category,
            'file_size' => $file->getSize(),
            'scan_result' => $scanResult
        ]);
        
        return $document;
    }
    
    /**
     * Validate uploaded file.
     */
    protected function validateFile(UploadedFile $file): void
    {
        $maxSize = config('documents.max_file_size', 10 * 1024 * 1024); // 10MB default
        $allowedExtensions = config('documents.allowed_extensions', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
        $allowedMimeTypes = config('documents.allowed_mime_types', [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
        
        // Check file size
        if ($file->getSize() > $maxSize) {
            throw new Exception('File size exceeds maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB');
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions));
        }
        
        // Check MIME type
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new Exception('Invalid file type detected');
        }
        
        // Check for executable files
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js'];
        if (in_array($extension, $dangerousExtensions)) {
            throw new Exception('Executable files are not allowed');
        }
    }
    
    /**
     * Perform virus scan on uploaded file.
     */
    protected function performVirusScan(UploadedFile $file): array
    {
        try {
            // In a real implementation, this would integrate with ClamAV or similar
            // For now, we'll do basic checks
            
            $filePath = $file->getRealPath();
            $fileContent = file_get_contents($filePath);
            
            // Check for suspicious patterns
            $suspiciousPatterns = [
                'eval(',
                'exec(',
                'system(',
                'shell_exec(',
                'passthru(',
                'base64_decode(',
                '<script',
                'javascript:',
                'vbscript:'
            ];
            
            foreach ($suspiciousPatterns as $pattern) {
                if (stripos($fileContent, $pattern) !== false) {
                    return [
                        'safe' => false,
                        'reason' => 'Suspicious content detected',
                        'pattern' => $pattern,
                        'scanned_at' => now()
                    ];
                }
            }
            
            // Check file signature
            $fileSignature = bin2hex(substr($fileContent, 0, 16));
            $knownMaliciousSignatures = [
                '4d5a90000300000004000000ffff0000', // PE executable
                '7f454c4601010100000000000000000000000000' // ELF executable
            ];
            
            if (in_array(substr($fileSignature, 0, 32), $knownMaliciousSignatures)) {
                return [
                    'safe' => false,
                    'reason' => 'Potentially malicious file signature',
                    'signature' => $fileSignature,
                    'scanned_at' => now()
                ];
            }
            
            return [
                'safe' => true,
                'scanned_at' => now(),
                'scan_engine' => 'basic_pattern_check',
                'file_hash' => hash('sha256', $fileContent)
            ];
            
        } catch (Exception $e) {
            Log::error('Virus scan failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            
            // Fail safe - reject file if scan fails
            return [
                'safe' => false,
                'reason' => 'Scan failed: ' . $e->getMessage(),
                'scanned_at' => now()
            ];
        }
    }
    
    /**
     * Store file securely with proper organization.
     */
    protected function storeFileSecurely(UploadedFile $file, int $userId): string
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        
        // Create directory structure: documents/user_id/year/month/day/
        $directory = "documents/{$userId}/{$year}/{$month}/{$day}";
        
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        // Store file
        $path = $file->storeAs($directory, $filename, 'private');
        
        return $path;
    }
    
    /**
     * Verify a document.
     */
    public function verifyDocument(UserDocument $document, User $verifier, bool $approved, string $notes = null): void
    {
        $status = $approved ? 'verified' : 'rejected';
        
        $document->update([
            'verification_status' => $status,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $notes
        ]);
        
        // Send notification to document owner
        $document->user->notify(
            new DocumentVerificationStatusChanged($document, $approved, $notes)
        );
        
        // Log verification
        Log::info('Document verification completed', [
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'verifier_id' => $verifier->id,
            'status' => $status,
            'notes' => $notes
        ]);
    }
    
    /**
     * Get documents pending verification.
     */
    public function getDocumentsForVerification()
    {
        return UserDocument::with(['user'])
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'asc');
    }
    
    /**
     * Get expiring documents.
     */
    public function getExpiringDocuments(int $days = 30)
    {
        return UserDocument::with(['user'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date');
    }
    
    /**
     * Get expired documents.
     */
    public function getExpiredDocuments()
    {
        return UserDocument::with(['user'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->orderBy('expiry_date', 'desc');
    }
    
    /**
     * Schedule expiration reminders.
     */
    public function scheduleExpirationReminders(UserDocument $document): void
    {
        if (!$document->expiry_date) {
            return;
        }
        
        $reminderDays = [30, 14, 7, 1];
        
        foreach ($reminderDays as $days) {
            $reminderDate = $document->expiry_date->subDays($days);
            
            if ($reminderDate->isFuture()) {
                // In a real implementation, this would use Laravel's job scheduling
                // For now, we'll just log the scheduled reminder
                Log::info('Expiration reminder scheduled', [
                    'document_id' => $document->id,
                    'user_id' => $document->user_id,
                    'reminder_date' => $reminderDate,
                    'days_before_expiry' => $days
                ]);
            }
        }
    }
    
    /**
     * Share a document with specified recipients.
     */
    public function shareDocument(UserDocument $document, array $recipients, array $permissions = [], Carbon $expiresAt = null): array
    {
        $shareToken = Str::uuid();
        
        // Store share information in cache
        $shareData = [
            'document_id' => $document->id,
            'shared_by' => $document->user_id,
            'recipients' => $recipients,
            'permissions' => array_merge([
                'view' => true,
                'download' => false,
                'print' => false
            ], $permissions),
            'expires_at' => $expiresAt,
            'created_at' => now()
        ];
        
        $cacheKey = "document_share:{$shareToken}";
        $cacheDuration = $expiresAt ? now()->diffInMinutes($expiresAt) : 60 * 24 * 7; // 7 days default
        
        Cache::put($cacheKey, $shareData, $cacheDuration);
        
        // Log document sharing
        Log::info('Document shared', [
            'document_id' => $document->id,
            'shared_by' => $document->user_id,
            'recipients' => $recipients,
            'share_token' => $shareToken,
            'expires_at' => $expiresAt
        ]);
        
        return [
            'share_url' => route('documents.shared', $shareToken),
            'share_token' => $shareToken,
            'expires_at' => $expiresAt,
            'permissions' => $shareData['permissions']
        ];
    }
    
    /**
     * Access a shared document.
     */
    public function accessSharedDocument(string $token, User $user = null): array
    {
        $cacheKey = "document_share:{$token}";
        $shareData = Cache::get($cacheKey);
        
        if (!$shareData) {
            throw new Exception('Share link has expired or is invalid');
        }
        
        $document = UserDocument::findOrFail($shareData['document_id']);
        
        // Check if user is authorized to access
        if ($user && !in_array($user->email, $shareData['recipients'])) {
            throw new Exception('You are not authorized to access this document');
        }
        
        // Log access
        Log::info('Shared document accessed', [
            'document_id' => $document->id,
            'share_token' => $token,
            'accessed_by' => $user?->id,
            'access_ip' => request()->ip()
        ]);
        
        return [
            'document' => $document,
            'permissions' => $shareData['permissions'],
            'shared_by' => User::find($shareData['shared_by']),
            'expires_at' => $shareData['expires_at']
        ];
    }
    
    /**
     * Create document backup.
     */
    public function createDocumentBackup(UserDocument $document): array
    {
        if (!Storage::disk('private')->exists($document->file_path)) {
            throw new Exception('Original document file not found');
        }
        
        $backupPath = 'backups/' . date('Y/m/d') . '/' . $document->id . '_' . time() . '_' . basename($document->file_path);
        
        // Copy file to backup location
        Storage::disk('private')->copy($document->file_path, $backupPath);
        
        // Store backup metadata
        $backupData = [
            'original_path' => $document->file_path,
            'backup_path' => $backupPath,
            'document_id' => $document->id,
            'user_id' => $document->user_id,
            'backup_size' => Storage::disk('private')->size($backupPath),
            'created_at' => now(),
            'backup_type' => 'manual'
        ];
        
        // Log backup creation
        Log::info('Document backup created', $backupData);
        
        return $backupData;
    }
    
    /**
     * Get user document statistics.
     */
    public function getUserDocumentStatistics(User $user): array
    {
        $documents = $user->documents();
        
        return [
            'total_documents' => $documents->count(),
            'verified_documents' => $documents->where('verification_status', 'verified')->count(),
            'pending_documents' => $documents->where('verification_status', 'pending')->count(),
            'rejected_documents' => $documents->where('verification_status', 'rejected')->count(),
            'total_storage_mb' => round($documents->sum('file_size') / 1024 / 1024, 2),
            'documents_by_category' => $documents->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'expiring_documents' => $documents->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())
                ->count(),
            'expired_documents' => $documents->whereNotNull('expiry_date')
                ->where('expiry_date', '<', now())
                ->count()
        ];
    }
    
    /**
     * Get platform document statistics.
     */
    public function getPlatformDocumentStatistics(): array
    {
        return Cache::remember('platform_document_stats', 300, function () {
            return [
                'total_documents' => UserDocument::count(),
                'pending_verification' => UserDocument::where('verification_status', 'pending')->count(),
                'verified_documents' => UserDocument::where('verification_status', 'verified')->count(),
                'rejected_documents' => UserDocument::where('verification_status', 'rejected')->count(),
                'total_storage_gb' => round(UserDocument::sum('file_size') / 1024 / 1024 / 1024, 2),
                'documents_uploaded_today' => UserDocument::whereDate('created_at', today())->count(),
                'documents_uploaded_this_week' => UserDocument::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'documents_uploaded_this_month' => UserDocument::whereMonth('created_at', now()->month)->count(),
                'average_verification_time_hours' => UserDocument::whereNotNull('verified_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as avg_hours')
                    ->value('avg_hours') ?? 0,
                'documents_by_category' => UserDocument::selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
                'active_users_with_documents' => UserDocument::distinct('user_id')->count('user_id')
            ];
        });
    }
}