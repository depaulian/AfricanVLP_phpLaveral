<?php

namespace App\Notifications;

use App\Models\UserAchievement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class AchievementEarned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public UserAchievement $userAchievement
    ) {
        $this->userAchievement->load('achievement');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        // Add email if user has email notifications enabled
        if ($notifiable->volunteer_notification_preferences['achievements'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $achievement = $this->userAchievement->achievement;
        
        return (new MailMessage)
            ->subject('🏆 Congratulations! You earned a new achievement')
            ->greeting('Congratulations, ' . $notifiable->name . '!')
            ->line('You have earned the "' . $achievement->name . '" achievement!')
            ->line($achievement->description)
            ->line('You earned ' . $achievement->points . ' points for this achievement.')
            ->action('View Your Achievements', route('client.volunteering.achievements'))
            ->line('Keep up the great work in making a difference in your community!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $achievement = $this->userAchievement->achievement;
        
        return [
            'type' => 'achievement_earned',
            'title' => 'Achievement Earned!',
            'message' => 'You earned the "' . $achievement->name . '" achievement!',
            'achievement_id' => $achievement->id,
            'achievement_name' => $achievement->name,
            'achievement_points' => $achievement->points,
            'earned_at' => $this->userAchievement->earned_at,
            'action_url' => route('client.volunteering.achievements'),
            'icon' => 'trophy',
            'color' => 'success'
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