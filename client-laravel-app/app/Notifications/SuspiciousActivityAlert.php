<?php

namespace App\Notifications;

use App\Models\SecurityEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousActivityAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private SecurityEvent $securityEvent
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = route('profile.security.dashboard');
        
        return (new MailMessage)
            ->subject('Security Alert: Suspicious Activity Detected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We detected suspicious activity on your account that requires your attention.')
            ->line('**Event:** ' . $this->securityEvent->getTypeLabel())
            ->line('**Description:** ' . $this->securityEvent->event_description)
            ->line('**Risk Level:** ' . ucfirst($this->securityEvent->risk_level))
            ->line('**Time:** ' . $this->securityEvent->created_at->format('M j, Y \a\t g:i A'))
            ->when($this->securityEvent->ip_address, function ($message) {
                return $message->line('**IP Address:** ' . $this->securityEvent->ip_address);
            })
            ->when($this->securityEvent->location_data, function ($message) {
                $location = $this->getLocationString();
                return $message->line('**Location:** ' . $location);
            })
            ->line('If this was you, you can safely ignore this message. If you don\'t recognize this activity, please review your account security immediately.')
            ->action('Review Security', $actionUrl)
            ->line('For your security, we recommend:')
            ->line('• Change your password if you suspect unauthorized access')
            ->line('• Enable two-factor authentication if not already enabled')
            ->line('• Review your active sessions and terminate any suspicious ones')
            ->line('• Check your recent account activity')
            ->line('Thank you for helping us keep your account secure.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'security_event_id' => $this->securityEvent->id,
            'event_type' => $this->securityEvent->event_type,
            'event_description' => $this->securityEvent->event_description,
            'risk_level' => $this->securityEvent->risk_level,
            'ip_address' => $this->securityEvent->ip_address,
            'location_data' => $this->securityEvent->location_data,
            'created_at' => $this->securityEvent->created_at->toISOString(),
            'action_url' => route('profile.security.dashboard'),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'security_alert';
    }

    /**
     * Get location string from location data.
     */
    private function getLocationString(): string
    {
        if (!$this->securityEvent->location_data) {
            return 'Unknown Location';
        }

        $parts = array_filter([
            $this->securityEvent->location_data['city'] ?? null,
            $this->securityEvent->location_data['region'] ?? null,
            $this->securityEvent->location_data['country'] ?? null,
        ]);

        return implode(', ', $parts) ?: 'Unknown Location';
    }
}