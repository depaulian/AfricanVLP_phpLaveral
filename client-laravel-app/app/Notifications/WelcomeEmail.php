<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

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
        return (new MailMessage)
            ->subject('Welcome to African Volunteer Platform!')
            ->greeting("Welcome, {$notifiable->first_name}!")
            ->line('Thank you for completing your registration on the African Volunteer Platform.')
            ->line('Your profile is now complete and you can start exploring volunteering opportunities that match your interests and skills.')
            ->line('Here are some things you can do next:')
            ->line('• Browse volunteering opportunities in your area')
            ->line('• Connect with organizations that align with your values')
            ->line('• Join our community forums to meet other volunteers')
            ->line('• Update your profile anytime to improve matching')
            ->action('Explore Opportunities', route('volunteering.index'))
            ->line('If you have any questions, our support team is here to help.')
            ->line('Happy volunteering!')
            ->salutation('Best regards, The African Volunteer Platform Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'message' => 'Welcome to African Volunteer Platform! Your registration is complete.',
            'action_url' => route('volunteering.index'),
            'action_text' => 'Explore Opportunities'
        ];
    }
}