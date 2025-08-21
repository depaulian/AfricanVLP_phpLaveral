<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class SuspiciousProfileAccess extends Notification implements ShouldQueue
{
    use Queueable;

    protected $accessor;
    protected $type;
    protected $details;

    /**
     * Create a new notification instance.
     */
    public function __construct($accessor, string $type, array $details)
    {
        $this->accessor = $accessor;
        $this->type = $type;
        $this->details = $details;
    }

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
        $accessorName = $this->accessor ? $this->accessor->name : 'Unknown user';
        
        return (new MailMessage)
            ->subject('Suspicious Activity Alert - Profile Access')
            ->greeting('Hello ' . $notifiable->name)
            ->line('We detected suspicious activity on your profile.')
            ->line('**Activity Type:** ' . $this->getTypeDescription())
            ->line('**User:** ' . $accessorName)
            ->line('**Details:** ' . $this->getDetailsDescription())
            ->line('If this activity seems suspicious, please review your privacy settings and consider changing your password.')
            ->action('Review Privacy Settings', url('/profile/privacy'))
            ->line('If you did not authorize this activity, please contact our support team immediately.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'suspicious_profile_access',
            'accessor_id' => $this->accessor?->id,
            'accessor_name' => $this->accessor?->name,
            'activity_type' => $this->type,
            'details' => $this->details,
            'timestamp' => now(),
        ];
    }

    /**
     * Get human-readable description of the activity type.
     */
    protected function getTypeDescription(): string
    {
        switch ($this->type) {
            case 'rapid_access':
                return 'Rapid successive profile access';
            case 'multiple_ips':
                return 'Access from multiple IP addresses';
            case 'unusual_location':
                return 'Access from unusual location';
            case 'bot_activity':
                return 'Potential automated access';
            default:
                return 'Suspicious activity';
        }
    }

    /**
     * Get human-readable description of the details.
     */
    protected function getDetailsDescription(): string
    {
        switch ($this->type) {
            case 'rapid_access':
                return "Profile accessed {$this->details['access_count']} times in {$this->details['time_window']}";
            case 'multiple_ips':
                return "Profile accessed from {$this->details['unique_ips']} different IP addresses in {$this->details['time_window']}";
            default:
                return json_encode($this->details);
        }
    }
}