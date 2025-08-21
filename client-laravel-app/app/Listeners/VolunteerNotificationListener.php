<?php

namespace App\Listeners;

use App\Events\VolunteerApplicationStatusChanged;
use App\Events\VolunteerTimeLogApproved;
use App\Events\VolunteerAssignmentCreated;
use App\Events\VolunteerAssignmentCompleted;
use App\Events\VolunteerCertificateIssued;
use App\Events\VolunteerAchievementEarned;
use App\Services\VolunteerNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class VolunteerNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private VolunteerNotificationService $notificationService
    ) {
        $this->queue = 'notifications';
    }

    /**
     * Handle application status change events
     */
    public function handleApplicationStatusChanged(VolunteerApplicationStatusChanged $event): void
    {
        try {
            $this->notificationService->sendApplicationStatusNotification(
                $event->application,
                $event->oldStatus,
                $event->newStatus
            );

            Log::info('Application status notification sent', [
                'application_id' => $event->application->id,
                'user_id' => $event->application->user_id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send application status notification', [
                'application_id' => $event->application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle time log approval events
     */
    public function handleTimeLogApproved(VolunteerTimeLogApproved $event): void
    {
        try {
            $this->notificationService->sendHourApprovalNotification(
                $event->timeLog,
                $event->approved,
                $event->feedback
            );

            Log::info('Time log approval notification sent', [
                'time_log_id' => $event->timeLog->id,
                'user_id' => $event->timeLog->user_id,
                'approved' => $event->approved,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send time log approval notification', [
                'time_log_id' => $event->timeLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle assignment created events
     */
    public function handleAssignmentCreated(VolunteerAssignmentCreated $event): void
    {
        try {
            $this->notificationService->sendAssignmentCreatedNotification($event->assignment);

            Log::info('Assignment created notification sent', [
                'assignment_id' => $event->assignment->id,
                'user_id' => $event->assignment->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send assignment created notification', [
                'assignment_id' => $event->assignment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle assignment completed events
     */
    public function handleAssignmentCompleted(VolunteerAssignmentCompleted $event): void
    {
        try {
            $this->notificationService->sendAssignmentCompletedNotification($event->assignment);

            Log::info('Assignment completed notification sent', [
                'assignment_id' => $event->assignment->id,
                'user_id' => $event->assignment->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send assignment completed notification', [
                'assignment_id' => $event->assignment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle certificate issued events
     */
    public function handleCertificateIssued(VolunteerCertificateIssued $event): void
    {
        try {
            $this->notificationService->sendCertificateIssuedNotification(
                $event->user,
                $event->assignment,
                $event->certificateUrl
            );

            Log::info('Certificate issued notification sent', [
                'assignment_id' => $event->assignment->id,
                'user_id' => $event->user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send certificate issued notification', [
                'assignment_id' => $event->assignment->id,
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle achievement earned events
     */
    public function handleAchievementEarned(VolunteerAchievementEarned $event): void
    {
        try {
            $this->notificationService->sendAchievementEarnedNotification(
                $event->user,
                $event->achievementName,
                $event->achievementDescription,
                $event->relatedModel
            );

            Log::info('Achievement earned notification sent', [
                'user_id' => $event->user->id,
                'achievement' => $event->achievementName,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send achievement earned notification', [
                'user_id' => $event->user->id,
                'achievement' => $event->achievementName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the event when it fails
     */
    public function failed($event, $exception): void
    {
        Log::error('Volunteer notification listener failed', [
            'event' => get_class($event),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}