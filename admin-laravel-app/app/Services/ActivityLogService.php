<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Log an activity
     */
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null
    ): ActivityLog {
        $user = $user ?? Auth::user();
        $request = request();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Log a model creation
     */
    public function logCreated(Model $model, array $properties = [], ?User $user = null): ActivityLog
    {
        $modelName = class_basename($model);
        return $this->log(
            ActivityLog::ACTION_CREATED,
            "Created {$modelName}",
            $model,
            array_merge(['attributes' => $model->getAttributes()], $properties),
            $user
        );
    }

    /**
     * Log a model update
     */
    public function logUpdated(Model $model, array $originalAttributes = [], array $properties = [], ?User $user = null): ActivityLog
    {
        $modelName = class_basename($model);
        $changes = $model->getChanges();
        
        return $this->log(
            ActivityLog::ACTION_UPDATED,
            "Updated {$modelName}",
            $model,
            array_merge([
                'old_values' => $originalAttributes,
                'new_values' => $changes
            ], $properties),
            $user
        );
    }

    /**
     * Log a model deletion
     */
    public function logDeleted(Model $model, array $properties = [], ?User $user = null): ActivityLog
    {
        $modelName = class_basename($model);
        return $this->log(
            ActivityLog::ACTION_DELETED,
            "Deleted {$modelName}",
            $model,
            array_merge(['attributes' => $model->getAttributes()], $properties),
            $user
        );
    }

    /**
     * Log user login
     */
    public function logLogin(User $user): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_LOGIN,
            'User logged in',
            $user,
            ['login_time' => now()],
            $user
        );
    }

    /**
     * Log user logout
     */
    public function logLogout(User $user): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_LOGOUT,
            'User logged out',
            $user,
            ['logout_time' => now()],
            $user
        );
    }

    /**
     * Log model approval
     */
    public function logApproved(Model $model, array $properties = [], ?User $user = null): ActivityLog
    {
        $modelName = class_basename($model);
        return $this->log(
            ActivityLog::ACTION_APPROVED,
            "Approved {$modelName}",
            $model,
            $properties,
            $user
        );
    }

    /**
     * Log model rejection
     */
    public function logRejected(Model $model, string $reason = '', array $properties = [], ?User $user = null): ActivityLog
    {
        $modelName = class_basename($model);
        return $this->log(
            ActivityLog::ACTION_REJECTED,
            "Rejected {$modelName}",
            $model,
            array_merge(['reason' => $reason], $properties),
            $user
        );
    }

    /**
     * Log content publishing
     */
    public function logPublished(Model $model, array $properties = [], ?User $user = null): ActivityLog
    {
        $modelName = class_basename($model);
        return $this->log(
            ActivityLog::ACTION_PUBLISHED,
            "Published {$modelName}",
            $model,
            $properties,
            $user
        );
    }

    /**
     * Log content unpublishing
     */
    public function logUnpublished(Model $model, array $properties = [], ?User $user = null): ActivityLog
    {
        $modelName = class_basename($model);
        return $this->log(
            ActivityLog::ACTION_UNPUBLISHED,
            "Unpublished {$modelName}",
            $model,
            $properties,
            $user
        );
    }

    /**
     * Log data export
     */
    public function logExport(string $exportType, array $properties = [], ?User $user = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_EXPORTED,
            "Exported {$exportType}",
            null,
            array_merge(['export_type' => $exportType], $properties),
            $user
        );
    }

    /**
     * Log data import
     */
    public function logImport(string $importType, array $properties = [], ?User $user = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_IMPORTED,
            "Imported {$importType}",
            null,
            array_merge(['import_type' => $importType], $properties),
            $user
        );
    }

    /**
     * Get activity logs with filters
     */
    public function getActivityLogs(array $filters = [])
    {
        $query = ActivityLog::with(['user', 'subject'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->ofAction($filters['action']);
        }

        if (isset($filters['subject_type'])) {
            $query->forSubjectType($filters['subject_type']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        return $query->paginate($filters['per_page'] ?? 25);
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(array $filters = []): array
    {
        $query = ActivityLog::query();

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        return [
            'total_activities' => $query->count(),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'actions_breakdown' => $query->groupBy('action')
                ->selectRaw('action, count(*) as count')
                ->pluck('count', 'action')
                ->toArray(),
            'daily_activities' => $query->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }
}
