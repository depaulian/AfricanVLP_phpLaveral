<?php

namespace App\Notifications;

use App\Models\VolunteeringOpportunity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class VolunteerMatchingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $opportunities;
    protected $matchType;

    /**
     * Create a new notification instance.
     */
    public function __construct($opportunities, string $matchType = 'recommended')
    {
        $this->opportunities = collect($opportunities);
        $this->matchType = $matchType;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Add email if user has email notifications enabled
        if ($notifiable->email_notifications_enabled ?? true) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $count = $this->opportunities->count();
        $subject = $this->getSubject($count);
        
        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line($this->getIntroLine($count));

        // Add up to 3 opportunities to the email
        $this->opportunities->take(3)->each(function ($opportunity) use ($mailMessage) {
            $mailMessage->line("• **{$opportunity->title}** at {$opportunity->organization->name}")
                        ->line("  Match Score: {$opportunity->match_score}%");
        });

        if ($count > 3) {
            $mailMessage->line("And " . ($count - 3) . " more opportunities waiting for you!");
        }

        return $mailMessage
            ->action('View Opportunities', route('client.volunteering.index'))
            ->line('Thank you for being part of our volunteer community!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'volunteer_matching',
            'match_type' => $this->matchType,
            'opportunities_count' => $this->opportunities->count(),
            'opportunities' => $this->opportunities->map(function ($opportunity) {
                return [
                    'id' => $opportunity->id,
                    'title' => $opportunity->title,
                    'organization' => $opportunity->organization->name,
                    'match_score' => $opportunity->match_score ?? 0,
                    'location_type' => $opportunity->location_type,
                    'city' => $opportunity->city->name ?? null,
                    'country' => $opportunity->country->name ?? null,
                ];
            })->toArray(),
            'message' => $this->getNotificationMessage(),
            'action_url' => route('client.volunteering.index'),
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Get subject line based on count
     */
    private function getSubject(int $count): string
    {
        return match ($this->matchType) {
            'new_matches' => "New volunteer opportunities match your interests!",
            'trending' => "Trending volunteer opportunities you might like",
            'similar' => "Opportunities similar to your previous volunteering",
            default => $count === 1 
                ? "We found a volunteer opportunity for you!"
                : "We found {$count} volunteer opportunities for you!"
        };
    }

    /**
     * Get intro line for email
     */
    private function getIntroLine(int $count): string
    {
        return match ($this->matchType) {
            'new_matches' => $count === 1
                ? "A new volunteer opportunity has been posted that matches your interests and skills!"
                : "{$count} new volunteer opportunities have been posted that match your interests and skills!",
            'trending' => $count === 1
                ? "There's a trending volunteer opportunity that might interest you!"
                : "There are {$count} trending volunteer opportunities that might interest you!",
            'similar' => $count === 1
                ? "Based on your volunteering history, we found an opportunity you might like!"
                : "Based on your volunteering history, we found {$count} opportunities you might like!",
            default => $count === 1
                ? "We found a volunteer opportunity that matches your profile!"
                : "We found {$count} volunteer opportunities that match your profile!"
        };
    }

    /**
     * Get notification message for database storage
     */
    private function getNotificationMessage(): string
    {
        $count = $this->opportunities->count();
        
        return match ($this->matchType) {
            'new_matches' => $count === 1
                ? "New volunteer opportunity matches your profile"
                : "{$count} new volunteer opportunities match your profile",
            'trending' => $count === 1
                ? "Trending volunteer opportunity for you"
                : "{$count} trending volunteer opportunities for you",
            'similar' => $count === 1
                ? "Opportunity similar to your volunteering history"
                : "{$count} opportunities similar to your volunteering history",
            default => $count === 1
                ? "Recommended volunteer opportunity"
                : "{$count} recommended volunteer opportunities"
        };
    }
}