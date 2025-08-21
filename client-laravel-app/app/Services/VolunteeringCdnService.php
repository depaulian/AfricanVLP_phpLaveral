<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class VolunteeringCdnService
{
    protected array $config;
    protected string $provider;

    public function __construct()
    {
        $this->config = config('cdn');
        $this->provider = $this->config['default'];
    }

    /**
     * Upload opportunity image to CDN
     */
    public function uploadOpportunityImage(UploadedFile $file, int $opportunityId): array
    {
        $folder = $this->config['volunteering']['opportunity_images']['folder'];
        $filename = "opportunity_{$opportunityId}_" . Str::random(8) . '.' . $file->getClientOriginalExtension();
        
        return $this->uploadFile($file, $folder, $filename, 'opportunity_image');
    }

    /**
     * Upload organization logo to CDN
     */
    public function uploadOrganizationLogo(UploadedFile $file, int $organizationId): array
    {
        $folder = $this->config['volunteering']['organization_logos']['folder'];
        $filename = "org_{$organizationId}_logo." . $file->getClientOriginalExtension();
        
        return $this->uploadFile($file, $folder, $filename, 'organization_logo');
    }

    /**
     * Upload volunteer certificate to CDN
     */
    public function uploadCertificate(string $pdfContent, int $userId, int $opportunityId): array
    {
        $folder = $this->config['volunteering']['certificates']['folder'];
        $filename = "certificate_{$userId}_{$opportunityId}_" . time() . '.pdf';
        
        if ($this->provider === 'cloudinary') {
            try {
                $result = Cloudinary::uploadFile($pdfContent, [
                    'folder' => $folder,
                    'public_id' => pathinfo($filename, PATHINFO_FILENAME),
                    'resource_type' => 'raw',
                    'format' => 'pdf'
                ]);

                return [
                    'success' => true,
                    'url' => $result->getSecurePath(),
                    'public_id' => $result->getPublicId(),
                    'provider' => 'cloudinary'
                ];
            } catch (\Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // Fallback to local storage
        $path = Storage::put($folder . '/' . $filename, $pdfContent);
        
        return [
            'success' => true,
            'url' => Storage::url($path),
            'path' => $path,
            'provider' => 'local'
        ];
    }

    /**
     * Upload document to CDN
     */
    public function uploadDocument(UploadedFile $file, string $type = 'general'): array
    {
        $folder = $this->config['volunteering']['documents']['folder'];
        $filename = $type . '_' . Str::random(8) . '_' . $file->getClientOriginalName();
        
        return $this->uploadFile($file, $folder, $filename, 'document');
    }

    /**
     * Generic file upload method
     */
    protected function uploadFile(UploadedFile $file, string $folder, string $filename, string $type): array
    {
        if (!$this->config['enabled']) {
            return $this->uploadToLocal($file, $folder, $filename);
        }

        switch ($this->provider) {
            case 'cloudinary':
                return $this->uploadToCloudinary($file, $folder, $filename, $type);
            case 'aws_s3':
                return $this->uploadToS3($file, $folder, $filename);
            default:
                return $this->uploadToLocal($file, $folder, $filename);
        }
    }

    /**
     * Upload to Cloudinary
     */
    protected function uploadToCloudinary(UploadedFile $file, string $folder, string $filename, string $type): array
    {
        try {
            $options = [
                'folder' => $folder,
                'public_id' => pathinfo($filename, PATHINFO_FILENAME),
            ];

            // Add optimization settings
            if ($this->config['optimization']['auto_format']) {
                $options['fetch_format'] = 'auto';
            }

            if ($this->config['optimization']['auto_quality']) {
                $options['quality'] = 'auto';
            }

            $result = Cloudinary::upload($file->getRealPath(), $options);

            // Generate transformation URLs
            $transformations = $this->generateTransformationUrls($result->getPublicId(), $type);

            return [
                'success' => true,
                'url' => $result->getSecurePath(),
                'public_id' => $result->getPublicId(),
                'transformations' => $transformations,
                'provider' => 'cloudinary',
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload to AWS S3
     */
    protected function uploadToS3(UploadedFile $file, string $folder, string $filename): array
    {
        try {
            $path = $folder . '/' . $filename;
            $uploaded = Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

            if ($uploaded) {
                return [
                    'success' => true,
                    'url' => Storage::disk('s3')->url($path),
                    'path' => $path,
                    'provider' => 's3',
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ];
            }

            return ['success' => false, 'error' => 'Upload failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload to local storage
     */
    protected function uploadToLocal(UploadedFile $file, string $folder, string $filename): array
    {
        try {
            $path = $file->storeAs($folder, $filename, 'public');

            return [
                'success' => true,
                'url' => Storage::url($path),
                'path' => $path,
                'provider' => 'local',
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate transformation URLs for different sizes
     */
    protected function generateTransformationUrls(string $publicId, string $type): array
    {
        if ($this->provider !== 'cloudinary') {
            return [];
        }

        $transformations = [];
        $typeConfig = null;

        // Get transformation config based on type
        switch ($type) {
            case 'opportunity_image':
                $typeConfig = $this->config['volunteering']['opportunity_images']['transformations'] ?? [];
                break;
            case 'organization_logo':
                $typeConfig = $this->config['volunteering']['organization_logos']['transformations'] ?? [];
                break;
            default:
                $typeConfig = $this->config['asset_types']['images']['transformations'] ?? [];
                break;
        }

        foreach ($typeConfig as $name => $params) {
            try {
                $transformations[$name] = Cloudinary::getUrl($publicId, $params);
            } catch (\Exception $e) {
                // Skip failed transformations
                continue;
            }
        }

        return $transformations;
    }

    /**
     * Delete file from CDN
     */
    public function deleteFile(string $publicId, string $provider = null): bool
    {
        $provider = $provider ?? $this->provider;

        try {
            switch ($provider) {
                case 'cloudinary':
                    $result = Cloudinary::destroy($publicId);
                    return $result['result'] === 'ok';

                case 'aws_s3':
                    return Storage::disk('s3')->delete($publicId);

                case 'local':
                    return Storage::disk('public')->delete($publicId);

                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get optimized image URL
     */
    public function getOptimizedImageUrl(string $publicId, string $transformation = 'medium', string $provider = null): string
    {
        $provider = $provider ?? $this->provider;

        if ($provider === 'cloudinary') {
            $transformations = $this->config['asset_types']['images']['transformations'][$transformation] ?? [];
            
            try {
                return Cloudinary::getUrl($publicId, $transformations);
            } catch (\Exception $e) {
                return $publicId; // Return original if transformation fails
            }
        }

        // For other providers, return the original URL
        return $publicId;
    }

    /**
     * Batch upload multiple files
     */
    public function batchUpload(array $files, string $folder, string $type): array
    {
        $results = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $filename = $type . '_' . $index . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $results[] = $this->uploadFile($file, $folder, $filename, $type);
            }
        }

        return $results;
    }

    /**
     * Get CDN statistics
     */
    public function getStatistics(): array
    {
        return Cache::remember('cdn_statistics', 3600, function () {
            $stats = [
                'provider' => $this->provider,
                'enabled' => $this->config['enabled'],
                'total_uploads' => 0,
                'storage_used' => 0,
                'bandwidth_used' => 0,
            ];

            // This would typically integrate with CDN provider APIs
            // For now, return basic info
            return $stats;
        });
    }

    /**
     * Purge CDN cache for specific URLs
     */
    public function purgeCache(array $urls): bool
    {
        if ($this->provider === 'cloudinary') {
            // Cloudinary doesn't have a direct cache purge API
            // Cache is automatically managed
            return true;
        }

        // For other CDN providers, implement cache purging
        return true;
    }

    /**
     * Generate signed URL for private content
     */
    public function generateSignedUrl(string $publicId, int $expiresIn = 3600): string
    {
        if ($this->provider === 'cloudinary') {
            try {
                return Cloudinary::getUrl($publicId, [
                    'sign_url' => true,
                    'expires_at' => time() + $expiresIn
                ]);
            } catch (\Exception $e) {
                return $publicId;
            }
        }

        return $publicId;
    }
}