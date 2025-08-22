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
        // Used by PageController@sitemap
        'modified' => 'datetime',
    ];

    /**
     * Creator relationship
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater relationship
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Sections relationship
     */
    public function pageSections()
    {
        return $this->hasMany(PageSection::class);
    }

    /**
     * Sliders relationship
     */
    public function sliders()
    {
        return $this->hasMany(Slider::class);
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeHomepage($query)
    {
        return $query->where('slug', 'home')->orWhere('slug', 'homepage');
    }

    public function scopeAbout($query)
    {
        return $query->where('slug', 'about');
    }

    /**
     * Accessors/Mutators for JSON columns
     */
    public function getSectionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setSectionsAttribute($value)
    {
        $this->attributes['sections'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getSettingsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Derived attributes
     */
    public function getUrlAttribute()
    {
        if ($this->slug === 'home' || $this->slug === 'homepage') {
            return url('/');
        }

        return url('/' . $this->slug);
    }

    public function isHomepage()
    {
        return in_array($this->slug, ['home', 'homepage']);
    }

    public function getTemplatePath()
    {
        return $this->template ?: 'client.pages.default';
    }
}
