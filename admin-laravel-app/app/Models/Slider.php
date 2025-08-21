<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image_url',
        'link_url',
        'link_text',
        'position',
        'status',
        'page_id',
        'show_overlay',
        'text_position',
        'animation_type',
        'settings',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'position' => 'integer',
        'show_overlay' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the page that owns the slider.
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the user who created the slider.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the slider.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active sliders.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive sliders.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for ordered sliders.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Scope for homepage sliders.
     */
    public function scopeHomepage($query)
    {
        return $query->whereHas('page', function ($q) {
            $q->where('slug', 'home')->orWhere('slug', 'homepage');
        });
    }

    /**
     * Get slider settings as array.
     */
    public function getSettingsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set slider settings.
     */
    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get optimized image URL.
     */
    public function getOptimizedImageUrl($width = null, $height = null)
    {
        if (!$this->image_url) {
            return null;
        }

        // If using Cloudinary, apply transformations
        if (strpos($this->image_url, 'cloudinary.com') !== false) {
            $transformations = [];
            
            if ($width) {
                $transformations[] = "w_{$width}";
            }
            
            if ($height) {
                $transformations[] = "h_{$height}";
            }
            
            $transformations[] = 'c_fill';
            $transformations[] = 'q_auto';
            $transformations[] = 'f_auto';
            
            $transformation = implode(',', $transformations);
            
            return str_replace('/upload/', "/upload/{$transformation}/", $this->image_url);
        }

        return $this->image_url;
    }

    /**
     * Check if slider has link.
     */
    public function hasLink()
    {
        return !empty($this->link_url);
    }

    /**
     * Get link target.
     */
    public function getLinkTarget()
    {
        $settings = $this->settings;
        return $settings['link_target'] ?? '_self';
    }
}