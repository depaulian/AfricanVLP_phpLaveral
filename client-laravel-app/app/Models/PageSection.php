<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'page_id',
        'section_type',
        'title',
        'subtitle',
        'content',
        'image_url',
        'settings',
        'position',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('section_type', $type);
    }

    /**
     * Accessors/Mutators
     */
    public function getSettingsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Helpers
     */
    public static function getSectionTypes()
    {
        return [
            'hero' => 'Hero Section',
            'about' => 'About Section',
            'features' => 'Features Section',
            'statistics' => 'Statistics Section',
            'testimonials' => 'Testimonials Section',
            'call_to_action' => 'Call to Action',
            'content_block' => 'Content Block',
            'image_gallery' => 'Image Gallery',
            'video' => 'Video Section',
            'contact' => 'Contact Section',
            'custom' => 'Custom Section',
        ];
    }

    public function getTypeLabel()
    {
        $types = self::getSectionTypes();
        return $types[$this->section_type] ?? $this->section_type;
    }

    public function getOptimizedImageUrl($width = null, $height = null)
    {
        if (!$this->image_url) {
            return null;
        }

        if (strpos($this->image_url, 'cloudinary.com') !== false) {
            $transformations = [];
            if ($width) $transformations[] = "w_{$width}";
            if ($height) $transformations[] = "h_{$height}";
            $transformations[] = 'c_fill';
            $transformations[] = 'q_auto';
            $transformations[] = 'f_auto';
            $transformation = implode(',', $transformations);
            return str_replace('/upload/', "/upload/{$transformation}/", $this->image_url);
        }

        return $this->image_url;
    }

    public function renderContent()
    {
        $content = $this->content;
        $content = str_replace(
            ['{{site_name}}', '{{current_year}}'],
            [config('app.name'), date('Y')],
            $content
        );
        return $content;
    }
}
