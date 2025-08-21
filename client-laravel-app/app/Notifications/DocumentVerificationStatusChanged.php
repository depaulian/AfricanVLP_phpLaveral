<?php

namespace App\Notifications;

use App\Models\UserDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentVerificationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserDocument $document;
    protected bool $approved;
    protected ?string $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(UserDocument $document, bool $approved, ?string $notes = null)
    {
        $this->document = $document;
        $this->approved = $approved;
        $this->notes = $notes;
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
        $mail = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->getMainMessage())
            ->line('Document: ' . $this->document->name)
            ->line('Category: ' . ucfirst($this->document->category))
            ->line('Verification Date: ' . $this->document->verified_at->format('F j, Y \a\t g:i A'));

        if ($this->notes) {
            $mail->line('Notes from reviewer: ' . $this->notes);
        }

        if ($this->approved) {
            $mail->line('✅ Your document has been successfully verified and is now active in your profile.')
                 ->action('View Document', route('profile.documents.show', $this->document))
                 ->line('Thank you for providing the necessary documentation. You can now take full advantage of all platform features.');
        } else {
            $mail->line('❌ Unfortunately, your document could not be verified at this time.')
                 ->line('Please review the feedback provided and upload a corrected version if needed.')
                 ->action('Upload New Document', route('profile.documents.create'))
                 ->line('If you have questions about this decision, please contact our support team.');
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_verification_status_changed',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'document_category' => $this->document->category,
            'verification_status' => $this->document->verification_status,
            'approved' => $this->approved,
            'verified_at' => $this->document->verified_at->toDateTimeString(),
            'verified_by' => $this->document->verifier?->name,
            'notes' => $this->notes,
            'message' => $this->getMainMessage(),
            'action_url' => $this->approved 
                ? route('profile.documents.show', $this->document)
                : route('profile.documents.create')
        ];
    }

    /**
     * Get the email subject.
     */
    protected function getSubject(): string
    {
        if ($this->approved) {
            return '✅ Document Verified - ' . $this->document->name;
        } else {
            return '❌ Document Verification Failed - ' . $this->document->name;
        }
    }

    /**
     * Get the main message content.
     */
    protected function getMainMessage(): string
    {
        if ($this->approved) {
            return "Great news! Your document '{$this->document->name}' has been successfully verified by our team.";
        } else {
            return "We were unable to verify your document '{$this->document->name}' at this time.";
        }
    }
}