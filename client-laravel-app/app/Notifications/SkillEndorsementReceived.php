<?php

namespace App\Notifications;

use App\Models\SkillEndorsement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SkillEndorsementReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private SkillEndorsement $endorsement) {}

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
            ->subject('Skill Endorsement Received')
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->endorsement->endorser->name} has endorsed your {$this->endorsement->skill->skill_name} skill!")
            ->when($this->endorsement->endorsement_comment, function ($mail) {
                return $mail->line("Comment: \"{$this->endorsement->endorsement_comment}\"");
            })
            ->line('This endorsement strengthens your skill profile and may help with volunteer opportunity matching.')
            ->action('View Your Skills', route('profile.skills.index'))
            ->line('Keep building your skills and helping others in the volunteer community!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'skill_endorsement_received',
            'endorsement_id' => $this->endorsement->id,
            'skill_id' => $this->endorsement->skill->id,
            'skill_name' => $this->endorsement->skill->skill_name,
            'endorser_id' => $this->endorsement->endorser->id,
            'endorser_name' => $this->endorsement->endorser->name,
            'comment' => $this->endorsement->endorsement_comment,
            'action_url' => route('profile.skills.index')
        ];
    }
}