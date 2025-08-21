<?php

namespace App\Notifications;

use App\Models\ProfileAchievement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ProfileAchievementEarned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ProfileAchievement $achievement
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        if ($notifiable->email_notifications_enabled) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 New Achievement Unlocked!')
            ->greeting('Congratulations!')
            ->line("You've earned a new achievement: **{$this->achievement->achievement_name}**")
            ->line($this->achievement->achievement_description)
            ->line("Points awarded: {$this->achievement->points_awarded}")
            ->action('View Your Achievements', route('profile.gamification.achievements'))
            ->line('Keep up the great work on your volunteering journey!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'achievement_earned',
            'achievement_id' => $this->achievement->id,
            'achievement_name' => $this->achievement->achievement_name,
            'achievement_description' => $this->achievement->achievement_description,
            'badge_icon' => $this->achievement->badge_icon,
            'badge_color' => $this->achievement->badge_color,
            'points_awarded' => $this->achievement->points_awarded,
            'is_featured' => $this->achievement->is_featured,
            'earned_at' => $this->achievement->earned_at,
            'action_url' => route('profile.gamification.achievements'),
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