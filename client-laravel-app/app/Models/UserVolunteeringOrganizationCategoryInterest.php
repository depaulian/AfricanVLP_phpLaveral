<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVolunteeringOrganizationCategoryInterest extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_volunteering_organization_category_interests';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'organization_category_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'organization_category_id' => 'integer',
    ];

    /**
     * Get the user that owns the organization category interest.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization category for this interest.
     */
    public function organizationCategory(): BelongsTo
    {
        return $this->belongsTo(OrganizationCategory::class);
    }
}