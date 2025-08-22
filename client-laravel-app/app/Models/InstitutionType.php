<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionType extends Model
{


    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
    ];

    /**
     * Get the organizations of this institution type.
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}