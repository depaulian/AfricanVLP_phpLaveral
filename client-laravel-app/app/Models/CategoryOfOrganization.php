<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryOfOrganization extends Model
{


    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
    ];

    /**
     * Get the organizations in this category.
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'category_id');
    }
}