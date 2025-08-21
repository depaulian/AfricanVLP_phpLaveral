<?php

namespace App\Notifications;

use App\Models\UserDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpirationReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserDocument $document;
    protected int $daysUntilExpiry;

    /**
     * Create a new notification instance.
     */
    public function __construct(UserDocument $document, int $daysUntilExpiry)
    {
        $this->document = $document;
        $this->daysUntilExpiry = $daysUntilExpiry;
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
        $urgencyLevel = $this->getUrgencyLevel();
        $subject = $this->getSubject();
        
        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->getMainMessage())
            ->line('Document: ' . $this->document->name)
            ->line('Category: ' . ucfirst($this->document->category))
            ->line('Expiry Date: ' . $this->document->expiry_date->format('F j, Y'))
            ->when($urgencyLevel === 'critical', function ($mail) {
                return $mail->line('⚠️ This is a critical reminder - please take immediate action.');
            })
            ->action('View Document', route('profile.documents.show', $this->document))
            ->line('To avoid any disruption to your account, please renew or update this document before it expires.')
            ->line('If you have already renewed this document, please upload the updated version to your profile.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_expiration_reminder',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'document_category' => $this->document->category,
            'expiry_date' => $this->document->expiry_date->toDateString(),
            'days_until_expiry' => $this->daysUntilExpiry,
            'urgency_level' => $this->getUrgencyLevel(),
            'message' => $this->getMainMessage(),
            'action_url' => route('profile.documents.show', $this->document)
        ];
    }

    /**
     * Get the urgency level based on days until expiry.
     */
    protected function getUrgencyLevel(): string
    {
        if ($this->daysUntilExpiry <= 1) {
            return 'critical';
        } elseif ($this->daysUntilExpiry <= 7) {
            return 'high';
        } elseif ($this->daysUntilExpiry <= 14) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get the email subject based on urgency.
     */
    protected function getSubject(): string
    {
        $urgency = $this->getUrgencyLevel();
        
        switch ($urgency) {
            case 'critical':
                return '🚨 URGENT: Document expires in ' . $this->daysUntilExpiry . ' day(s)';
            case 'high':
                return '⚠️ Important: Document expires in ' . $this->daysUntilExpiry . ' days';
            case 'medium':
                return 'Reminder: Document expires in ' . $this->daysUntilExpiry . ' days';
            default:
                return 'Document Expiration Reminder - ' . $this->daysUntilExpiry . ' days remaining';
        }
    }

    /**
     * Get the main message content.
     */
    protected function getMainMessage(): string
    {
        $urgency = $this->getUrgencyLevel();
        
        switch ($urgency) {
            case 'critical':
                return "Your document '{$this->document->name}' expires in {$this->daysUntilExpiry} day(s). Immediate action is required to avoid any service disruption.";
            case 'high':
                return "Your document '{$this->document->name}' expires in {$this->daysUntilExpiry} days. Please renew or update it soon.";
            case 'medium':
                return "Your document '{$this->document->name}' expires in {$this->daysUntilExpiry} days. We recommend renewing it at your earliest convenience.";
            default:
                return "This is a friendly reminder that your document '{$this->document->name}' expires in {$this->daysUntilExpiry} days.";
        }
    }
}