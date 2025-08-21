<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationAbandonmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private array $registrationProgress
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
        $completionPercentage = $this->registrationProgress['overall_percentage'];
        $nextStep = $this->registrationProgress['next_step'];
        $nextStepTitle = $this->registrationProgress['steps'][$nextStep]['title'] ?? 'Complete Registration';

        $message = (new MailMessage)
            ->subject('Complete Your Profile - African Volunteer Platform')
            ->greeting("Hi {$notifiable->first_name},")
            ->line("We noticed you started creating your profile on the African Volunteer Platform but haven't finished yet.")
            ->line("You're {$completionPercentage}% complete! Just a few more steps and you'll be ready to start making a difference.");

        if ($completionPercentage > 0) {
            $message->line("Your next step is: **{$nextStepTitle}**");
        }

        $message->line('Completing your profile helps us:')
            ->line('• Match you with volunteering opportunities that fit your interests')
            ->line('• Connect you with organizations in your area')
            ->line('• Provide personalized recommendations based on your skills')
            ->action('Continue Registration', route('registration.step', $nextStep ?? 'basic_info'))
            ->line('It only takes a few minutes to complete, and you can make a real impact in your community.')
            ->line('If you need any help, feel free to reach out to our support team.')
            ->salutation('Best regards, The African Volunteer Platform Team');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'registration_reminder',
            'message' => 'Complete your registration to start volunteering',
            'completion_percentage' => $this->registrationProgress['overall_percentage'],
            'next_step' => $this->registrationProgress['next_step'],
            'action_url' => route('registration.step', $this->registrationProgress['next_step'] ?? 'basic_info'),
            'action_text' => 'Continue Registration'
        ];
    }
}