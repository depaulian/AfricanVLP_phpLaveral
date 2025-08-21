<?php

namespace App\Notifications;

use App\Models\VolunteerNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VolunteerEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private VolunteerNotification $volunteerNotification
    ) {
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $notification = $this->volunteerNotification;
        $mailMessage = (new MailMessage)
            ->subject($notification->title)
            ->greeting("Hello {$notifiable->name}!")
            ->line($notification->message);

        // Add type-specific content and actions
        switch ($notification->type) {
            case 'opportunity_match':
                $mailMessage = $this->buildOpportunityMatchEmail($mailMessage, $notification);
                break;
                
            case 'application_status':
                $mailMessage = $this->buildApplicationStatusEmail($mailMessage, $notification);
                break;
                
            case 'hour_approval':
                $mailMessage = $this->buildHourApprovalEmail($mailMessage, $notification);
                break;
                
            case 'deadline_reminder':
                $mailMessage = $this->buildDeadlineReminderEmail($mailMessage, $notification);
                break;
                
            case 'supervisor_notification':
                $mailMessage = $this->buildSupervisorNotificationEmail($mailMessage, $notification);
                break;
                
            case 'digest':
                $mailMessage = $this->buildDigestEmail($mailMessage, $notification);
                break;
                
            case 'assignment_created':
                $mailMessage = $this->buildAssignmentCreatedEmail($mailMessage, $notification);
                break;
                
            case 'certificate_issued':
                $mailMessage = $this->buildCertificateIssuedEmail($mailMessage, $notification);
                break;
                
            case 'achievement_earned':
                $mailMessage = $this->buildAchievementEarnedEmail($mailMessage, $notification);
                break;
        }

        // Add common footer
        $mailMessage->line('Thank you for your dedication to volunteering!')
                   ->salutation('Best regards, The Volunteering Team');

        // Add action button if URL is available
        if ($notification->url) {
            $mailMessage->action('View Details', $notification->url);
        }

        return $mailMessage;
    }

    /**
     * Build opportunity match email content
     */
    private function buildOpportunityMatchEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        $matchScore = $data['match_score'] ?? 0;
        
        return $mailMessage
            ->line("We found a great volunteering opportunity that matches your interests with a {$matchScore}% compatibility score!")
            ->line("**Opportunity:** {$data['opportunity_title']}")
            ->line("**Organization:** {$data['organization_name']}")
            ->line('This opportunity aligns well with your skills and interests. Don\'t miss out on this chance to make a difference!');
    }

    /**
     * Build application status email content
     */
    private function buildApplicationStatusEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        $status = $data['new_status'];
        
        $mailMessage->line("**Opportunity:** {$data['opportunity_title']}")
                   ->line("**Organization:** {$data['organization_name']}")
                   ->line("**Status:** " . ucfirst($status));

        if ($status === 'approved') {
            $mailMessage->line('🎉 Congratulations! You can now start your volunteer journey.')
                       ->line('You will receive further instructions about your assignment soon.');
        } elseif ($status === 'rejected') {
            $mailMessage->line('While this opportunity didn\'t work out, don\'t be discouraged!')
                       ->line('There are many other opportunities waiting for you.');
        }

        return $mailMessage;
    }

    /**
     * Build hour approval email content
     */
    private function buildHourApprovalEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        $approved = $data['approved'];
        $hours = $data['hours'];
        $date = $data['date'];
        
        $mailMessage->line("**Hours:** {$hours}")
                   ->line("**Date:** {$date}");

        if ($approved) {
            $mailMessage->line('✅ Your hours have been approved and added to your volunteer record.');
        } else {
            $mailMessage->line('⚠️ Your hours need revision. Please check the feedback and resubmit if necessary.');
            
            if (isset($data['feedback'])) {
                $mailMessage->line("**Feedback:** {$data['feedback']}");
            }
        }

        return $mailMessage;
    }

    /**
     * Build deadline reminder email content
     */
    private function buildDeadlineReminderEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        $deadlineType = $data['deadline_type'];
        $daysUntil = $data['days_until'];
        $isOverdue = $data['is_overdue'] ?? false;
        
        if ($isOverdue) {
            $mailMessage->line("⚠️ **OVERDUE:** Your {$deadlineType} deadline has passed.");
        } elseif ($daysUntil <= 1) {
            $mailMessage->line("🚨 **URGENT:** Your {$deadlineType} deadline is " . ($daysUntil === 0 ? 'today' : 'tomorrow') . "!");
        } else {
            $mailMessage->line("📅 **REMINDER:** Your {$deadlineType} deadline is in {$daysUntil} days.");
        }

        return $mailMessage->line('Please take action as soon as possible to avoid any issues.');
    }

    /**
     * Build supervisor notification email content
     */
    private function buildSupervisorNotificationEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        $notificationType = $data['notification_type'];
        
        switch ($notificationType) {
            case 'pending_approval':
                $mailMessage->line('You have volunteer time logs waiting for your approval.')
                           ->line('Please review and approve them at your earliest convenience.');
                break;
                
            case 'new_application':
                $mailMessage->line('A new volunteer application has been submitted and requires your review.')
                           ->line('Please evaluate the application and provide your decision.');
                break;
                
            case 'volunteer_assigned':
                if (isset($data['volunteer_name'])) {
                    $mailMessage->line("**Volunteer:** {$data['volunteer_name']}");
                }
                $mailMessage->line('A new volunteer has been assigned to your supervision.')
                           ->line('Please reach out to them to begin the onboarding process.');
                break;
        }

        return $mailMessage;
    }

    /**
     * Build digest email content
     */
    private function buildDigestEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        
        $mailMessage->line('Here\'s your weekly volunteer activity summary:');

        // Application updates
        if (!empty($data['application_updates'])) {
            $mailMessage->line('**📋 Application Updates:**');
            foreach ($data['application_updates'] as $update) {
                $mailMessage->line("• {$update['opportunity_title']} - Status: {$update['status']}");
            }
        }

        // Completed hours
        if (!empty($data['completed_hours']) && $data['completed_hours']['total_hours'] > 0) {
            $hours = $data['completed_hours'];
            $mailMessage->line("**⏰ Hours Completed:** {$hours['total_hours']} hours in {$hours['sessions']} sessions");
        }

        // Upcoming deadlines
        if (!empty($data['upcoming_deadlines'])) {
            $mailMessage->line('**📅 Upcoming Deadlines:**');
            foreach ($data['upcoming_deadlines'] as $deadline) {
                $mailMessage->line("• {$deadline['title']} - Due in {$deadline['days_until']} days");
            }
        }

        return $mailMessage->line('Keep up the great work! Your contributions make a real difference.');
    }

    /**
     * Build assignment created email content
     */
    private function buildAssignmentCreatedEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        
        return $mailMessage
            ->line("**Opportunity:** {$data['opportunity_title']}")
            ->line("**Organization:** {$data['organization_name']}")
            ->line("**Start Date:** {$data['start_date']}")
            ->line('You will receive more details about your assignment soon. Get ready to make an impact!');
    }

    /**
     * Build certificate issued email content
     */
    private function buildCertificateIssuedEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        
        $mailMessage->line("🏆 Congratulations on completing your volunteer service!")
                   ->line("**Opportunity:** {$data['opportunity_title']}")
                   ->line("**Organization:** {$data['organization_name']}")
                   ->line("**Total Hours:** {$data['total_hours']} hours");

        if (isset($data['certificate_url'])) {
            $mailMessage->action('Download Certificate', $data['certificate_url']);
        }

        return $mailMessage->line('Thank you for your valuable contribution to the community!');
    }

    /**
     * Build achievement earned email content
     */
    private function buildAchievementEarnedEmail(MailMessage $mailMessage, VolunteerNotification $notification): MailMessage
    {
        $data = $notification->data;
        
        return $mailMessage
            ->line("🏅 **Achievement Unlocked:** {$data['achievement_name']}")
            ->line($data['achievement_description'])
            ->line('Your dedication and hard work have been recognized. Keep up the excellent work!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->volunteerNotification->id,
            'type' => $this->volunteerNotification->type,
            'title' => $this->volunteerNotification->title,
            'message' => $this->volunteerNotification->message,
            'data' => $this->volunteerNotification->data,
        ];
    }
}