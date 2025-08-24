<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVolunteeringInterest extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_volunteering_interests';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'category_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'category_id' => 'integer',
    ];

    /**
     * Get the user that owns the volunteering interest.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the volunteering category for this interest.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VolunteeringCategory::class);
    }
}