<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resource extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'title',
        'description',
        'content',
        'organization_id',
        'resource_type_id',
        'status',
        'featured',
        'download_count',
        'view_count',
        'tags',
        'author',
        'published_date',
        'language',
        'access_level',
        'external_url'
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'published_date' => 'datetime',
        'featured' => 'boolean',
        'download_count' => 'integer',
        'view_count' => 'integer',
        'tags' => 'array'
    ];

    /**
     * Get the organization that owns this resource.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the resource type.
     */
    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }

    /**
     * Get the files associated with this resource.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ResourceFile::class);
    }

    /**
     * Get the categories associated with this resource.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CategoryOfResource::class, 'resource_categories', 'resource_id', 'category_id');
    }

    /**
     * Check if the resource is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the resource is featured.
     */
    public function isFeatured(): bool
    {
        return $this->featured;
    }

    /**
     * Increment the download count.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Increment the view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Get the primary file (first file) for this resource.
     */
    public function getPrimaryFile(): ?ResourceFile
    {
        return $this->files()->first();
    }

    /**
     * Get all image files for this resource.
     */
    public function getImageFiles()
    {
        return $this->files()->where('file_category', 'images')->get();
    }

    /**
     * Get all document files for this resource.
     */
    public function getDocumentFiles()
    {
        return $this->files()->where('file_category', 'documents')->get();
    }

    /**
     * Check if resource has files.
     */
    public function hasFiles(): bool
    {
        return $this->files()->count() > 0;
    }

    /**
     * Get the resource's thumbnail URL.
     */
    public function getThumbnailUrl(): ?string
    {
        $primaryFile = $this->getPrimaryFile();
        
        if ($primaryFile && $primaryFile->file_category === 'images') {
            return $primaryFile->getThumbnailUrl();
        }
        
        return null;
    }

    /**
     * Scope for published resources.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for featured resources.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope for resources by organization.
     */
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}