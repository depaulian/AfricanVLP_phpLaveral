<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationRegistrationWelcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Organization $organization
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
        return (new MailMessage)
            ->subject('Welcome to AU-VLP - Organization Registration Complete')
            ->greeting('Welcome to the African Union Volunteer Platform!')
            ->line("Thank you for registering {$this->organization->name} with AU-VLP.")
            ->line('Your organization registration has been submitted and is currently under review by our team.')
            ->line('**What happens next:**')
            ->line('1. **Email Verification**: Please verify your email address by clicking the verification link we sent to your inbox.')
            ->line('2. **Document Review**: Our team will review your organization\'s registration documents.')
            ->line('3. **Account Activation**: Once approved, you\'ll receive an email notification and can start posting volunteer opportunities.')
            ->line('**Important Information:**')
            ->line('- Your organization status is currently "Pending Approval"')
            ->line('- You will receive email notifications about your registration status')
            ->line('- The review process typically takes 2-3 business days')
            ->action('Verify Email Address', url('/email/verify'))
            ->line('If you have any questions or need assistance, please don\'t hesitate to contact our support team.')
            ->line('Thank you for joining our mission to promote volunteerism for peace and development across Africa!')
            ->salutation('Best regards, The AU-VLP Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'organization_registration_welcome',
            'organization_id' => $this->organization->id,
            'organization_name' => $this->organization->name,
            'message' => "Welcome to AU-VLP! Your organization {$this->organization->name} has been registered and is pending approval.",
        ];
    }
}
