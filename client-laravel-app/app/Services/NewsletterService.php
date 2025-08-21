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
     * Get subscription status for email
     *
     * @param string $email
     * @return array
     */
    public function getSubscriptionStatus(string $email): array
    {
        $subscription = NewsletterSubscription::where('email', $email)->first();
        
        if (!$subscription) {
            return [
                'subscribed' => false,
                'status' => 'not_found',
                'subscription' => null
            ];
        }

        return [
            'subscribed' => $subscription->status === 'active',
            'status' => $subscription->status,
            'subscription' => $subscription
        ];
    }

    /**
     * Update subscription preferences
     *
     * @param string $email
     * @param array $preferences
     * @return array
     */
    public function updatePreferences(string $email, array $preferences): array
    {
        try {
            $subscription = NewsletterSubscription::where('email', $email)->first();
            
            if (!$subscription) {
                return [
                    'success' => false,
                    'message' => 'Subscription not found'
                ];
            }

            $subscription->update([
                'preferences' => $preferences
            ]);

            return [
                'success' => true,
                'message' => 'Preferences updated successfully',
                'subscription' => $subscription
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update newsletter preferences: ' . $e->getMessage(), [
                'email' => $email,
                'preferences' => $preferences
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update preferences: ' . $e->getMessage()
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
     * Generate verification token
     *
     * @return string
     */
    protected function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}