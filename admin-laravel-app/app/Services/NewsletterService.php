<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\NewsletterSubscription;
use App\Models\NewsletterContent;
use SendGrid\Mail\Mail as SendGridMail;
use SendGrid;

class NewsletterService
{
    protected $config;
    protected $sendGrid;

    public function __construct()
    {
        $this->config = config('services.newsletter');
        
        if ($this->config['provider'] === 'sendgrid') {
            $sendGridConfig = config('services.sendgrid');
            if ($sendGridConfig['api_key']) {
                $this->sendGrid = new SendGrid($sendGridConfig['api_key']);
            }
        }
    }

    /**
     * Subscribe email to newsletter
     *
     * @param string $email
     * @param array $preferences
     * @return array
     */
    public function subscribe(string $email, array $preferences = []): array
    {
        try {
            // Check if already subscribed
            $existing = NewsletterSubscription::where('email', $email)->first();
            
            if ($existing) {
                if ($existing->status === 'active') {
                    return [
                        'success' => false,
                        'message' => 'Email is already subscribed to the newsletter',
                        'subscription' => $existing
                    ];
                } else {
                    // Reactivate subscription
                    $existing->update([
                        'status' => 'active',
                        'preferences' => $preferences,
                        'subscribed_at' => now(),
                        'unsubscribed_at' => null
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Newsletter subscription reactivated successfully',
                        'subscription' => $existing
                    ];
                }
            }

            // Create new subscription
            $subscription = NewsletterSubscription::create([
                'email' => $email,
                'status' => 'active',
                'preferences' => $preferences,
                'subscribed_at' => now(),
                'verification_token' => $this->generateVerificationToken()
            ]);

            // Send welcome email
            $this->sendWelcomeEmail($subscription);

            return [
                'success' => true,
                'message' => 'Successfully subscribed to newsletter',
                'subscription' => $subscription
            ];

        } catch (\Exception $e) {
            Log::error('Newsletter subscription failed: ' . $e->getMessage(), [
                'email' => $email,
                'preferences' => $preferences
            ]);

            return [
                'success' => false,
                'message' => 'Newsletter subscription failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Unsubscribe email from newsletter
     *
     * @param string $email
     * @param string|null $token
     * @return array
     */
    public function unsubscribe(string $email, ?string $token = null): array
    {
        try {
            $query = NewsletterSubscription::where('email', $email);
            
            if ($token) {
                $query->where('verification_token', $token);
            }
            
            $subscription = $query->first();
            
            if (!$subscription) {
                return [
                    'success' => false,
                    'message' => 'Subscription not found'
                ];
            }

            $subscription->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Successfully unsubscribed from newsletter',
                'subscription' => $subscription
            ];

        } catch (\Exception $e) {
            Log::error('Newsletter unsubscription failed: ' . $e->getMessage(), [
                'email' => $email,
                'token' => $token
            ]);

            return [
                'success' => false,
                'message' => 'Newsletter unsubscription failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send newsletter to all active subscribers
     *
     * @param NewsletterContent $content
     * @return array
     */
    public function sendNewsletter(NewsletterContent $content): array
    {
        if (!$this->config['enabled']) {
            return [
                'success' => false,
                'message' => 'Newsletter service is disabled'
            ];
        }

        try {
            $subscribers = NewsletterSubscription::where('status', 'active')->get();
            
            if ($subscribers->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No active subscribers found'
                ];
            }

            $sent = 0;
            $failed = 0;
            $errors = [];

            foreach ($subscribers as $subscriber) {
                try {
                    $this->sendNewsletterEmail($subscriber, $content);
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'email' => $subscriber->email,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Failed to send newsletter to: ' . $subscriber->email, [
                        'error' => $e->getMessage(),
                        'content_id' => $content->id
                    ]);
                }
            }

            // Update content status
            $content->update([
                'sent_at' => now(),
                'sent_count' => $sent,
                'failed_count' => $failed,
                'status' => 'sent'
            ]);

            return [
                'success' => true,
                'message' => "Newsletter sent successfully to {$sent} subscribers",
                'sent' => $sent,
                'failed' => $failed,
                'errors' => $errors,
                'total_subscribers' => $subscribers->count()
            ];

        } catch (\Exception $e) {
            Log::error('Newsletter sending failed: ' . $e->getMessage(), [
                'content_id' => $content->id
            ]);

            return [
                'success' => false,
                'message' => 'Newsletter sending failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create newsletter content
     *
     * @param array $data
     * @return array
     */
    public function createContent(array $data): array
    {
        try {
            $content = NewsletterContent::create([
                'title' => $data['title'],
                'subject' => $data['subject'],
                'content' => $data['content'],
                'template' => $data['template'] ?? 'default',
                'status' => $data['status'] ?? 'draft',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_by' => $data['created_by'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Newsletter content created successfully',
                'content' => $content
            ];

        } catch (\Exception $e) {
            Log::error('Newsletter content creation failed: ' . $e->getMessage(), $data);

            return [
                'success' => false,
                'message' => 'Newsletter content creation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get subscription statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        try {
            $stats = [
                'total_subscribers' => NewsletterSubscription::count(),
                'active_subscribers' => NewsletterSubscription::where('status', 'active')->count(),
                'unsubscribed' => NewsletterSubscription::where('status', 'unsubscribed')->count(),
                'pending_verification' => NewsletterSubscription::where('status', 'pending')->count(),
                'total_newsletters_sent' => NewsletterContent::where('status', 'sent')->count(),
                'recent_subscriptions' => NewsletterSubscription::where('subscribed_at', '>=', now()->subDays(30))->count(),
                'recent_unsubscriptions' => NewsletterSubscription::where('unsubscribed_at', '>=', now()->subDays(30))->count()
            ];

            // Calculate growth rate
            $previousMonthSubscriptions = NewsletterSubscription::where('subscribed_at', '>=', now()->subDays(60))
                                                               ->where('subscribed_at', '<', now()->subDays(30))
                                                               ->count();
            
            if ($previousMonthSubscriptions > 0) {
                $stats['growth_rate'] = (($stats['recent_subscriptions'] - $previousMonthSubscriptions) / $previousMonthSubscriptions) * 100;
            } else {
                $stats['growth_rate'] = $stats['recent_subscriptions'] > 0 ? 100 : 0;
            }

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get newsletter stats: ' . $e->getMessage());
            
            return [
                'total_subscribers' => 0,
                'active_subscribers' => 0,
                'unsubscribed' => 0,
                'pending_verification' => 0,
                'total_newsletters_sent' => 0,
                'recent_subscriptions' => 0,
                'recent_unsubscriptions' => 0,
                'growth_rate' => 0
            ];
        }
    }

    /**
     * Send welcome email to new subscriber
     *
     * @param NewsletterSubscription $subscription
     * @return void
     */
    protected function sendWelcomeEmail(NewsletterSubscription $subscription): void
    {
        if ($this->config['provider'] === 'sendgrid' && $this->sendGrid) {
            $this->sendWelcomeEmailViaSendGrid($subscription);
        } else {
            // Fallback to Laravel Mail
            Mail::send('emails.newsletter.welcome', ['subscription' => $subscription], function ($message) use ($subscription) {
                $message->to($subscription->email)
                        ->subject('Welcome to our Newsletter!');
            });
        }
    }

    /**
     * Send newsletter email to subscriber
     *
     * @param NewsletterSubscription $subscription
     * @param NewsletterContent $content
     * @return void
     */
    protected function sendNewsletterEmail(NewsletterSubscription $subscription, NewsletterContent $content): void
    {
        if ($this->config['provider'] === 'sendgrid' && $this->sendGrid) {
            $this->sendNewsletterViaSendGrid($subscription, $content);
        } else {
            // Fallback to Laravel Mail
            Mail::send('emails.newsletter.content', [
                'subscription' => $subscription,
                'content' => $content
            ], function ($message) use ($subscription, $content) {
                $message->to($subscription->email)
                        ->subject($content->subject);
            });
        }
    }

    /**
     * Send welcome email via SendGrid
     *
     * @param NewsletterSubscription $subscription
     * @return void
     */
    protected function sendWelcomeEmailViaSendGrid(NewsletterSubscription $subscription): void
    {
        $email = new SendGridMail();
        $email->setFrom(config('services.sendgrid.from_email'), config('services.sendgrid.from_name'));
        $email->setSubject('Welcome to our Newsletter!');
        $email->addTo($subscription->email);
        
        $unsubscribeUrl = route('newsletter.unsubscribe', [
            'email' => $subscription->email,
            'token' => $subscription->verification_token
        ]);
        
        $htmlContent = view('emails.newsletter.welcome', [
            'subscription' => $subscription,
            'unsubscribe_url' => $unsubscribeUrl
        ])->render();
        
        $email->addContent("text/html", $htmlContent);
        
        $this->sendGrid->send($email);
    }

    /**
     * Send newsletter via SendGrid
     *
     * @param NewsletterSubscription $subscription
     * @param NewsletterContent $content
     * @return void
     */
    protected function sendNewsletterViaSendGrid(NewsletterSubscription $subscription, NewsletterContent $content): void
    {
        $email = new SendGridMail();
        $email->setFrom(config('services.sendgrid.from_email'), config('services.sendgrid.from_name'));
        $email->setSubject($content->subject);
        $email->addTo($subscription->email);
        
        $unsubscribeUrl = route('newsletter.unsubscribe', [
            'email' => $subscription->email,
            'token' => $subscription->verification_token
        ]);
        
        $htmlContent = view('emails.newsletter.content', [
            'subscription' => $subscription,
            'content' => $content,
            'unsubscribe_url' => $unsubscribeUrl
        ])->render();
        
        $email->addContent("text/html", $htmlContent);
        
        $this->sendGrid->send($email);
    }

    /**
     * Generate verification token
     *
     * @return string
     */
    protected function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}