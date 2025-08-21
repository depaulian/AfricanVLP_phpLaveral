<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationRegistrationAbandonmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $sessionId,
        private array $progress
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $completedSteps = $this->progress['completed_steps'] ?? 0;
        $totalSteps = $this->progress['total_steps'] ?? 4;
        $percentage = $this->progress['overall_percentage'] ?? 0;

        return (new MailMessage)
            ->subject('Complete Your Organization Registration - AU-VLP')
            ->greeting('Hello!')
            ->line('We noticed you started registering your organization with the African Union Volunteer Platform but haven\'t finished yet.')
            ->line("You're {$percentage}% complete ({$completedSteps} of {$totalSteps} steps finished).")
            ->line('**Why complete your registration?**')
            ->line('• Connect with volunteers across Africa')
            ->line('• Post volunteer opportunities for your organization')
            ->line('• Access AU-VLP resources and network')
            ->line('• Contribute to peace and development initiatives')
            ->line('**It only takes a few more minutes to complete!**')
            ->action('Continue Registration', route('registration.organization.start'))
            ->line('If you\'re experiencing any issues or have questions, our support team is here to help.')
            ->line('This registration link will remain active for 30 days.')
            ->salutation('Best regards, The AU-VLP Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'organization_registration_abandonment',
            'session_id' => $this->sessionId,
            'progress' => $this->progress,
            'message' => 'Complete your organization registration with AU-VLP',
        ];
    }
}
