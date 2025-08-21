<?php

namespace App\Notifications;

use App\Models\VolunteerCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VolunteerCertificate $certificate
    ) {
        $this->certificate->load(['organization', 'assignment.opportunity']);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        // Add email if user has email notifications enabled
        if ($notifiable->volunteer_notification_preferences['certificates'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $certificate = $this->certificate;
        
        return (new MailMessage)
            ->subject('📜 Your Volunteer Certificate is Ready!')
            ->greeting('Congratulations, ' . $notifiable->name . '!')
            ->line('Your volunteer certificate has been issued by ' . $certificate->organization->name . '.')
            ->line('Certificate: ' . $certificate->title)
            ->line($certificate->description)
            ->when($certificate->hours_completed, function ($message) use ($certificate) {
                return $message->line('Hours completed: ' . $certificate->formatted_hours);
            })
            ->action('Download Certificate', route('client.certificates.download', $certificate))
            ->line('Thank you for your valuable contribution to the community!')
            ->line('Certificate Number: ' . $certificate->certificate_number);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $certificate = $this->certificate;
        
        return [
            'type' => 'certificate_issued',
            'title' => 'Certificate Issued!',
            'message' => 'Your volunteer certificate from ' . $certificate->organization->name . ' is ready.',
            'certificate_id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'certificate_title' => $certificate->title,
            'organization_name' => $certificate->organization->name,
            'issued_at' => $certificate->issued_at,
            'action_url' => route('client.certificates.show', $certificate),
            'download_url' => route('client.certificates.download', $certificate),
            'icon' => 'certificate',
            'color' => 'info'
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}