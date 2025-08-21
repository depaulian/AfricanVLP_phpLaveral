<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudinaryService
{
    protected $cloudName;
    protected $apiKey;
    protected $apiSecret;
    protected $baseUrl;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
        $this->baseUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}";
    }

    /**
     * Upload an image to Cloudinary
     */
    public function uploadImage(UploadedFile $file, array $options = []): array
    {
        try {
            $defaultOptions = [
                'folder' => 'africavlp/images',
                'resource_type' => 'image',
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ];

            $uploadOptions = array_merge($defaultOptions, $options);
            return $this->upload($file, $uploadOptions);
        } catch (Exception $e) {
            Log::error('Cloudinary image upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload a file to Cloudinary
     */
    public function uploadFile(UploadedFile $file, array $options = []): array
    {
        try {
            $defaultOptions = [
                'folder' => 'africavlp/files',
                'resource_type' => 'auto',
            ];

            $uploadOptions = array_merge($defaultOptions, $options);
            return $this->upload($file, $uploadOptions);
        } catch (Exception $e) {
            Log::error('Cloudinary file upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload profile image with multiple sizes
     */
    public function uploadProfileImage(UploadedFile $file, string $userId): array
    {
        try {
            $publicId = "africavlp/profiles/{$userId}/" . time();
            
            $options = [
                'public_id' => $publicId,
                'folder' => 'africavlp/profiles',
                'resource_type' => 'image',
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'eager' => [
                    ['width' => 150, 'height' => 150, 'crop' => 'fill', 'gravity' => 'face'],
                    ['width' => 300, 'height' => 300, 'crop' => 'fill', 'gravity' => 'face'],
                    ['width' => 600, 'height' => 600, 'crop' => 'fill', 'gravity' => 'face'],
                ],
            ];

            $result = $this->upload($file, $options);

            return [
                'public_id' => $result['public_id'],
                'original_url' => $result['secure_url'],
                'thumbnail_url' => $this->generateTransformationUrl($result['public_id'], ['width' => 150, 'height' => 150, 'crop' => 'fill']),
                'medium_url' => $this->generateTransformationUrl($result['public_id'], ['width' => 300, 'height' => 300, 'crop' => 'fill']),
                'large_url' => $this->generateTransformationUrl($result['public_id'], ['width' => 600, 'height' => 600, 'crop' => 'fill']),
                'file_size' => $result['bytes'],
                'format' => $result['format'],
            ];
        } catch (Exception $e) {
            Log::error('Cloudinary profile image upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload forum attachment
     */
    public function uploadForumAttachment(UploadedFile $file, string $threadId): array
    {
        try {
            $publicId = "africavlp/forums/{$threadId}/" . time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            
            $options = [
                'public_id' => $publicId,
                'folder' => 'africavlp/forums',
                'resource_type' => 'auto',
            ];

            return $this->upload($file, $options);
        } catch (Exception $e) {
            Log::error('Cloudinary forum attachment upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload organization image
     */
    public function uploadOrganizationImage(UploadedFile $file, string $organizationId): array
    {
        try {
            $publicId = "africavlp/organizations/{$organizationId}/" . time();
            
            $options = [
                'public_id' => $publicId,
                'folder' => 'africavlp/organizations',
                'resource_type' => 'image',
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'eager' => [
                    ['width' => 200, 'height' => 200, 'crop' => 'fit'],
                    ['width' => 400, 'height' => 400, 'crop' => 'fit'],
                ],
            ];

            return $this->upload($file, $options);
        } catch (Exception $e) {
            Log::error('Cloudinary organization image upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload event image
     */
    public function uploadEventImage(UploadedFile $file, string $eventId): array
    {
        try {
            $publicId = "africavlp/events/{$eventId}/" . time();
            
            $options = [
                'public_id' => $publicId,
                'folder' => 'africavlp/events',
                'resource_type' => 'image',
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'eager' => [
                    ['width' => 400, 'height' => 300, 'crop' => 'fill'],
                    ['width' => 800, 'height' => 600, 'crop' => 'fill'],
                ],
            ];

            return $this->upload($file, $options);
        } catch (Exception $e) {
            Log::error('Cloudinary event image upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload document/verification file
     */
    public function uploadDocument(UploadedFile $file, string $userId, string $type = 'document'): array
    {
        try {
            $publicId = "africavlp/documents/{$userId}/{$type}/" . time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            
            $options = [
                'public_id' => $publicId,
                'folder' => 'africavlp/documents',
                'resource_type' => 'auto',
            ];

            return $this->upload($file, $options);
        } catch (Exception $e) {
            Log::error('Cloudinary document upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload volunteer portfolio item
     */
    public function uploadPortfolioItem(UploadedFile $file, string $userId): array
    {
        try {
            $publicId = "africavlp/portfolio/{$userId}/" . time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            
            $options = [
                'public_id' => $publicId,
                'folder' => 'africavlp/portfolio',
                'resource_type' => 'auto',
            ];

            return $this->upload($file, $options);
        } catch (Exception $e) {
            Log::error('Cloudinary portfolio upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a file from Cloudinary
     */
    public function deleteFile(string $publicId): bool
    {
        try {
            $timestamp = time();
            $signature = $this->generateSignature([
                'public_id' => $publicId,
                'timestamp' => $timestamp
            ]);

            $response = Http::asForm()->post("{$this->baseUrl}/image/destroy", [
                'public_id' => $publicId,
                'timestamp' => $timestamp,
                'api_key' => $this->apiKey,
                'signature' => $signature,
            ]);

            $result = $response->json();
            return $result['result'] === 'ok';
        } catch (Exception $e) {
            Log::error('Cloudinary file deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate transformation URL
     */
    public function generateTransformationUrl(string $publicId, array $transformations = []): string
    {
        $baseUrl = "https://res.cloudinary.com/{$this->cloudName}/image/upload";
        
        if (!empty($transformations)) {
            $transformString = $this->buildTransformationString($transformations);
            return "{$baseUrl}/{$transformString}/{$publicId}";
        }
        
        return "{$baseUrl}/{$publicId}";
    }

    /**
     * Get file info from Cloudinary
     */
    public function getFileInfo(string $publicId): ?array
    {
        try {
            $timestamp = time();
            $signature = $this->generateSignature([
                'public_id' => $publicId,
                'timestamp' => $timestamp
            ]);

            $response = Http::get("{$this->baseUrl}/resources/image", [
                'public_ids' => $publicId,
                'timestamp' => $timestamp,
                'api_key' => $this->apiKey,
                'signature' => $signature,
            ]);

            $result = $response->json();
            return $result['resources'][0] ?? null;
        } catch (Exception $e) {
            Log::error('Cloudinary file info retrieval failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Core upload method
     */
    protected function upload(UploadedFile $file, array $options): array
    {
        $timestamp = time();
        $params = array_merge($options, [
            'timestamp' => $timestamp,
            'api_key' => $this->apiKey,
        ]);

        $signature = $this->generateSignature($params);
        $params['signature'] = $signature;

        $response = Http::attach(
            'file', file_get_contents($file->getPathname()), $file->getClientOriginalName()
        )->post("{$this->baseUrl}/{$options['resource_type']}/upload", $params);

        if (!$response->successful()) {
            throw new Exception('Cloudinary upload failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Generate signature for Cloudinary API
     */
    protected function generateSignature(array $params): string
    {
        unset($params['api_key'], $params['signature']);
        ksort($params);
        
        $paramString = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $paramString .= "{$key}={$value}&";
        }
        
        $paramString = rtrim($paramString, '&') . $this->apiSecret;
        return sha1($paramString);
    }

    /**
     * Build transformation string for URL
     */
    protected function buildTransformationString(array $transformations): string
    {
        $parts = [];
        foreach ($transformations as $key => $value) {
            $parts[] = "{$key}_{$value}";
        }
        return implode(',', $parts);
    }

    /**
     * Upload resource file
     */
    public function uploadResource(UploadedFile $file, string $resourceId): array
    {
        try {
            $publicId = "africavlp/resources/{$resourceId}/" . time() . '_' . pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            
            $options = [
                'public_id' => $publicId,
                'folder' => 'africavlp/resources',
                'resource_type' => 'auto',
            ];

            return $this->upload($file, $options);
        } catch (Exception $e) {
            Log::error('Cloudinary resource upload failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
