<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logActivity(ActivityLog::ACTION_CREATED, 'Created');
        });

        static::updated(function ($model) {
            if ($model->wasChanged()) {
                $model->logActivity(ActivityLog::ACTION_UPDATED, 'Updated', [
                    'old_values' => $model->getOriginal(),
                    'new_values' => $model->getChanges()
                ]);
            }
        });

        static::deleted(function ($model) {
            $model->logActivity(ActivityLog::ACTION_DELETED, 'Deleted');
        });
    }

    /**
     * Get all activity logs for this model
     */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    /**
     * Log an activity for this model
     */
    public function logActivity(
        string $action, 
        string $description = null, 
        array $properties = []
    ): ActivityLog {
        $activityLogService = app(ActivityLogService::class);
        
        $description = $description ?? ucfirst($action) . ' ' . class_basename($this);
        
        return $activityLogService->log(
            $action,
            $description,
            $this,
            $properties
        );
    }

    /**
     * Get the latest activity for this model
     */
    public function getLatestActivity(): ?ActivityLog
    {
        return $this->activityLogs()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get activities by action type
     */
    public function getActivitiesByAction(string $action)
    {
        return $this->activityLogs()
            ->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if model has any activity logs
     */
    public function hasActivityLogs(): bool
    {
        return $this->activityLogs()->exists();
    }

    /**
     * Get activity summary for this model
     */
    public function getActivitySummary(): array
    {
        $logs = $this->activityLogs;
        
        return [
            'total_activities' => $logs->count(),
            'first_activity' => $logs->sortBy('created_at')->first()?->created_at,
            'last_activity' => $logs->sortByDesc('created_at')->first()?->created_at,
            'actions_count' => $logs->groupBy('action')->map->count(),
            'unique_users' => $logs->pluck('user_id')->unique()->count(),
        ];
    }
}
