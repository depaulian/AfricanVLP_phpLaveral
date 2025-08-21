<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'template',
        'sections',
        'settings',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sections' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created the page.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the page.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get page sections.
     */
    public function pageSections()
    {
        return $this->hasMany(PageSection::class);
    }

    /**
     * Get page sliders.
     */
    public function sliders()
    {
        return $this->hasMany(Slider::class);
    }

    /**
     * Scope for published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get page by slug.
     */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Get homepage.
     */
    public function scopeHomepage($query)
    {
        return $query->where('slug', 'home')->orWhere('slug', 'homepage');
    }

    /**
     * Get about page.
     */
    public function scopeAbout($query)
    {
        return $query->where('slug', 'about');
    }

    /**
     * Get page sections as array.
     */
    public function getSectionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set page sections.
     */
    public function setSectionsAttribute($value)
    {
        $this->attributes['sections'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get page settings as array.
     */
    public function getSettingsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set page settings.
     */
    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get full URL for the page.
     */
    public function getUrlAttribute()
    {
        if ($this->slug === 'home' || $this->slug === 'homepage') {
            return url('/');
        }
        
        return url('/' . $this->slug);
    }

    /**
     * Check if page is homepage.
     */
    public function isHomepage()
    {
        return in_array($this->slug, ['home', 'homepage']);
    }

    /**
     * Get page template path.
     */
    public function getTemplatePath()
    {
        return $this->template ?: 'client.pages.default';
    }
}