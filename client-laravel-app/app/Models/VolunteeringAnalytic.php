<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class VolunteeringAnalytic extends Model
{
    use HasFactory;

    protected $table = 'volunteering_analytics';

    protected $fillable = [
        'organization_id',
        'period_type',
        'period_start',
        'period_end',
        'metric_type',
        'metric_category',
        'value',
        'metadata',
        'calculated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'calculated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // Scopes expected by the controller
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeMetricType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('metric_category', $category);
    }

    public function scopeDateRange($query, $start, $end)
    {
        $start = $start instanceof Carbon ? $start : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end : Carbon::parse($end);
        return $query->whereDate('period_start', '>=', $start->toDateString())
                     ->whereDate('period_end', '<=', $end->toDateString());
    }
}
