<?php

namespace App\Listeners;

use App\Events\VolunteeringOpportunityCreated;
use App\Services\VolunteerNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyMatchingVolunteers implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        private VolunteerNotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(VolunteeringOpportunityCreated $event): void
    {
        try {
            // Only send notifications for active opportunities that are accepting applications
            if ($event->opportunity->status === 'active' && $event->opportunity->is_accepting_applications) {
                $this->notificationService->notifyMatchingVolunteers($event->opportunity);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify matching volunteers', [
                'opportunity_id' => $event->opportunity->id,
                'error' => $e->getMessage()
            ]);
            
            // Don't fail the job, just log the error
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(VolunteeringOpportunityCreated $event, \Throwable $exception): void
    {
        Log::error('NotifyMatchingVolunteers job failed', [
            'opportunity_id' => $event->opportunity->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}