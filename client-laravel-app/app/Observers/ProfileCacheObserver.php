<?php

namespace App\Observers;

use App\Services\ProfileCacheInvalidationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProfileCacheObserver
{
    protected ProfileCacheInvalidationService $invalidationService;

    public function __construct(ProfileCacheInvalidationService $invalidationService)
    {
        $this->invalidationService = $invalidationService;
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->handleEvent($model, 'created');
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Get changed fields for smart invalidation
        $changedFields = array_keys($model->getDirty());
        
        if (!empty($changedFields)) {
            $this->invalidationService->smartInvalidation($model, $changedFields);
        } else {
            $this->handleEvent($model, 'updated');
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->handleEvent($model, 'deleted');
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->handleEvent($model, 'restored');
    }

    /**
     * Handle model events
     */
    protected function handleEvent(Model $model, string $event): void
    {
        try {
            $this->invalidationService->handleModelEvent($model, $event);
        } catch (\Exception $e) {
            Log::error('Profile cache observer failed', [
                'model' => get_class($model),
                'event' => $event,
                'model_id' => $model->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}