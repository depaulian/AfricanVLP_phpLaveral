<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\UserAlumniOrganization;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class ProfileCacheInvalidationService
{
    protected ProfileCacheService $cacheService;

    public function __construct(ProfileCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle model events and invalidate appropriate caches
     */
    public function handleModelEvent(Model $model, string $event): void
    {
        $userId = $this->extractUserId($model);
        
        if (!$userId) {
            return;
        }

        switch (get_class($model)) {
            case UserProfile::class:
                $this->handleProfileEvent($userId, $event);
                break;
                
            case UserSkill::class:
                $this->handleSkillEvent($userId, $event);
                break;
                
            case UserVolunteeringInterest::class:
                $this->handleInterestEvent($userId, $event);
                break;
                
            case UserVolunteeringHistory::class:
                $this->handleHistoryEvent($userId, $event);
                break;
                
            case UserDocument::class:
                $this->handleDocumentEvent($userId, $event);
                break;
                
            case UserAlumniOrganization::class:
                $this->handleAlumniEvent($userId, $event);
                break;
                
            case User::class:
                $this->handleUserEvent($userId, $event);
                break;
        }
    }

    /**
     * Extract user ID from model
     */
    protected function extractUserId(Model $model): ?int
    {
        if ($model instanceof User) {
            return $model->id;
        }
        
        if (isset($model->user_id)) {
            return $model->user_id;
        }
        
        return null;
    }

    /**
     * Handle profile model events
     */
    protected function handleProfileEvent(int $userId, string $event): void
    {
        switch ($event) {
            case 'created':
            case 'updated':
            case 'deleted':
                $this->cacheService->invalidateSpecificCache($userId, 'profile');
                $this->cacheService->invalidateSpecificCache($userId, 'complete');
                $this->cacheService->invalidateSpecificCache($userId, 'stats');
                break;
        }
        
        Log::info('Profile cache invalidated due to profile event', [
            'user_id' => $userId,
            'event' => $event
        ]);
    }

    /**
     * Handle skill model events
     */
    protected function handleSkillEvent(int $userId, string $event): void
    {
        switch ($event) {
            case 'created':
            case 'updated':
            case 'deleted':
                $this->cacheService->invalidateSpecificCache($userId, 'skills');
                $this->cacheService->invalidateSpecificCache($userId, 'complete');
                $this->cacheService->invalidateSpecificCache($userId, 'stats');
                break;
        }
        
        Log::info('Profile cache invalidated due to skill event', [
            'user_id' => $userId,
            'event' => $event
        ]);
    }

    /**
     * Handle interest model events
     */
    protected function handleInterestEvent(int $userId, string $event): void
    {
        switch ($event) {
            case 'created':
            case 'updated':
            case 'deleted':
                $this->cacheService->invalidateSpecificCache($userId, 'interests');
                $this->cacheService->invalidateSpecificCache($userId, 'complete');
                $this->cacheService->invalidateSpecificCache($userId, 'stats');
                break;
        }
        
        Log::info('Profile cache invalidated due to interest event', [
            'user_id' => $userId,
            'event' => $event
        ]);
    }

    /**
     * Handle history model events
     */
    protected function handleHistoryEvent(int $userId, string $event): void
    {
        switch ($event) {
            case 'created':
            case 'updated':
            case 'deleted':
                $this->cacheService->invalidateSpecificCache($userId, 'history');
                $this->cacheService->invalidateSpecificCache($userId, 'complete');
                $this->cacheService->invalidateSpecificCache($userId, 'stats');
                break;
        }
        
        Log::info('Profile cache invalidated due to history event', [
            'user_id' => $userId,
            'event' => $event
        ]);
    }

    /**
     * Handle document model events
     */
    protected function handleDocumentEvent(int $userId, string $event): void
    {
        switch ($event) {
            case 'created':
            case 'updated':
            case 'deleted':
                $this->cacheService->invalidateSpecificCache($userId, 'documents');
                $this->cacheService->invalidateSpecificCache($userId, 'complete');
                $this->cacheService->invalidateSpecificCache($userId, 'stats');
                break;
        }
        
        Log::info('Profile cache invalidated due to document event', [
            'user_id' => $userId,
            'event' => $event
        ]);
    }

    /**
     * Handle alumni model events
     */
    protected function handleAlumniEvent(int $userId, string $event): void
    {
        switch ($event) {
            case 'created':
            case 'updated':
            case 'deleted':
                $this->cacheService->invalidateSpecificCache($userId, 'complete');
                $this->cacheService->invalidateSpecificCache($userId, 'stats');
                break;
        }
        
        Log::info('Profile cache invalidated due to alumni event', [
            'user_id' => $userId,
            'event' => $event
        ]);
    }

    /**
     * Handle user model events
     */
    protected function handleUserEvent(int $userId, string $event): void
    {
        switch ($event) {
            case 'updated':
                // Only invalidate if relevant fields changed
                $this->cacheService->invalidateSpecificCache($userId, 'complete');
                break;
                
            case 'deleted':
                // Clear all caches for deleted user
                $this->cacheService->invalidateUserCache($userId);
                break;
        }
        
        Log::info('Profile cache invalidated due to user event', [
            'user_id' => $userId,
            'event' => $event
        ]);
    }

    /**
     * Invalidate caches for multiple users
     */
    public function invalidateMultipleUsers(array $userIds, string $type = null): void
    {
        foreach ($userIds as $userId) {
            if ($type) {
                $this->cacheService->invalidateSpecificCache($userId, $type);
            } else {
                $this->cacheService->invalidateUserCache($userId);
            }
        }
        
        Log::info('Multiple user caches invalidated', [
            'user_count' => count($userIds),
            'type' => $type
        ]);
    }

    /**
     * Smart invalidation based on field changes
     */
    public function smartInvalidation(Model $model, array $changedFields): void
    {
        $userId = $this->extractUserId($model);
        
        if (!$userId) {
            return;
        }

        $cacheTypesToInvalidate = $this->determineCacheTypes($model, $changedFields);
        
        foreach ($cacheTypesToInvalidate as $type) {
            $this->cacheService->invalidateSpecificCache($userId, $type);
        }
        
        Log::info('Smart cache invalidation performed', [
            'user_id' => $userId,
            'model' => get_class($model),
            'changed_fields' => $changedFields,
            'invalidated_types' => $cacheTypesToInvalidate
        ]);
    }

    /**
     * Determine which cache types to invalidate based on changed fields
     */
    protected function determineCacheTypes(Model $model, array $changedFields): array
    {
        $types = ['complete']; // Always invalidate complete profile
        
        if ($model instanceof UserProfile) {
            $types[] = 'profile';
            
            if (array_intersect($changedFields, ['bio', 'phone_number', 'address', 'city_id', 'country_id'])) {
                $types[] = 'stats';
            }
        }
        
        if ($model instanceof UserSkill) {
            $types[] = 'skills';
            $types[] = 'stats';
        }
        
        if ($model instanceof UserVolunteeringInterest) {
            $types[] = 'interests';
            $types[] = 'stats';
        }
        
        if ($model instanceof UserVolunteeringHistory) {
            $types[] = 'history';
            $types[] = 'stats';
        }
        
        if ($model instanceof UserDocument) {
            $types[] = 'documents';
            $types[] = 'stats';
        }
        
        return array_unique($types);
    }
}