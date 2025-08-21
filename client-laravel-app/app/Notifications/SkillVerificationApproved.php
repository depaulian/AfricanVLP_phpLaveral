<?php

namespace App\Notifications;

use App\Models\UserSkill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SkillVerificationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private UserSkill $skill) {}

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
            ->subject('Skill Verification Approved')
            ->greeting("Hello {$notifiable->name}!")
            ->line("Congratulations! Your {$this->skill->skill_name} skill has been officially verified.")
            ->line('This verification badge will be displayed on your profile and will help you stand out to organizations looking for volunteers with your expertise.')
            ->line('Verified skills carry more weight in our matching algorithm and may lead to better volunteer opportunities.')
            ->action('View Your Verified Skills', route('profile.skills.index'))
            ->line('Thank you for being part of our verified volunteer community!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'skill_verification_approved',
            'skill_id' => $this->skill->id,
            'skill_name' => $this->skill->skill_name,
            'verification_type' => $this->skill->verification_type,
            'verified_at' => $this->skill->verified_at,
            'action_url' => route('profile.skills.index')
        ];
    }
}