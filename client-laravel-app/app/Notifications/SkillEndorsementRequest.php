<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SkillEndorsementRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private UserSkill $skill,
        private User $skillOwner,
        private ?string $message = null
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
        return (new MailMessage)
            ->subject('Skill Endorsement Request')
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->skillOwner->name} has requested your endorsement for their {$this->skill->skill_name} skill.")
            ->when($this->message, function ($mail) {
                return $mail->line("Message: \"{$this->message}\"");
            })
            ->line('Your endorsement would help verify their expertise and could be valuable for their volunteer applications.')
            ->action('Review Endorsement Request', route('profile.skills.endorsements.show', $this->skill))
            ->line('Thank you for supporting the volunteer community!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'skill_endorsement_request',
            'skill_id' => $this->skill->id,
            'skill_name' => $this->skill->skill_name,
            'skill_owner_id' => $this->skillOwner->id,
            'skill_owner_name' => $this->skillOwner->name,
            'message' => $this->message,
            'action_url' => route('profile.skills.endorsements.show', $this->skill)
        ];
    }
}