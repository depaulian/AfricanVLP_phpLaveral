<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'status',
        'sort_order',
        'meta_title',
        'meta_description'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    protected $dates = ['deleted_at'];

    // Boot method to generate slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Relationships
    public function blogs()
    {
        return $this->hasMany(Blog::class, 'category_id');
    }

    public function publishedBlogs()
    {
        return $this->hasMany(Blog::class, 'category_id')->published();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getBlogsCount()
    {
        return $this->blogs()->count();
    }

    public function getPublishedBlogsCount()
    {
        return $this->publishedBlogs()->count();
    }

    public function getUrl()
    {
        return route('blog.category', $this->slug);
    }
}
