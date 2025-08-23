<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'file_extensions',
        'max_file_size',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'file_extensions' => 'array',
        'max_file_size' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the resources of this type.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Check if this resource type is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if a file extension is allowed for this resource type.
     */
    public function isExtensionAllowed(string $extension): bool
    {
        if (!$this->file_extensions) {
            return true; // If no restrictions, allow all
        }
        
        return in_array(strtolower($extension), array_map('strtolower', $this->file_extensions));
    }

    /**
     * Check if a file size is within the allowed limit.
     */
    public function isFileSizeAllowed(int $fileSize): bool
    {
        if (!$this->max_file_size) {
            return true; // If no limit set, allow any size
        }
        
        return $fileSize <= $this->max_file_size;
    }

    /**
     * Get the maximum file size in human readable format.
     */
    public function getMaxFileSizeFormatted(): string
    {
        if (!$this->max_file_size) {
            return 'No limit';
        }
        
        $bytes = $this->max_file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get allowed extensions as a formatted string.
     */
    public function getAllowedExtensionsString(): string
    {
        if (!$this->file_extensions || empty($this->file_extensions)) {
            return 'All file types';
        }
        
        return implode(', ', array_map('strtoupper', $this->file_extensions));
    }

    /**
     * Get the icon URL or return a default icon.
     */
    public function getIconUrl(): string
    {
        if ($this->icon) {
            return asset('storage/icons/' . $this->icon);
        }
        return asset('images/default-resource-type-icon.svg');
    }

    /**
     * Get resources count for this type.
     */
    public function getResourcesCount(): int
    {
        return $this->resources()->count();
    }

    /**
     * Get active resources count for this type.
     */
    public function getActiveResourcesCount(): int
    {
        return $this->resources()->where('status', 'published')->count();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Validate if a file can be uploaded for this resource type.
     */
    public function validateFile(string $filename, int $fileSize): array
    {
        $errors = [];
        
        // Check file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!$this->isExtensionAllowed($extension)) {
            $errors[] = "File extension '{$extension}' is not allowed. Allowed extensions: " . $this->getAllowedExtensionsString();
        }
        
        // Check file size
        if (!$this->isFileSizeAllowed($fileSize)) {
            $errors[] = "File size exceeds the maximum allowed size of " . $this->getMaxFileSizeFormatted();
        }
        
        return $errors;
    }

    /**
     * Get common file extensions by category.
     */
    public static function getCommonExtensions(): array
    {
        return [
            'documents' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'],
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'],
            'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
            'audio' => ['mp3', 'wav', 'flac', 'aac', 'ogg'],
            'archives' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'spreadsheets' => ['xls', 'xlsx', 'csv', 'ods'],
            'presentations' => ['ppt', 'pptx', 'odp'],
        ];
    }
}