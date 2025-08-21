<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\DeliverWebhook;

class WebhookService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('volunteering.integrations.webhooks', []);
    }

    /**
     * Register a new webhook
     */
    public function register(string $url, array $events, array $options = []): Webhook
    {
        return Webhook::create([
            'url' => $url,
            'events' => $events,
            'secret' => $options['secret'] ?? $this->generateSecret(),
            'active' => $options['active'] ?? true,
            'verify_ssl' => $options['verify_ssl'] ?? $this->config['verify_ssl'] ?? true,
            'timeout' => $options['timeout'] ?? $this->config['timeout'] ?? 30,
            'max_retries' => $options['max_retries'] ?? $this->config['max_retries'] ?? 3,
            'metadata' => $options['metadata'] ?? []
        ]);
    }

    /**
     * Trigger webhook for an event
     */
    public function trigger(string $event, array $payload, array $context = []): void
    {
        if (!$this->config['enabled'] ?? true) {
            return;
        }

        $webhooks = Webhook::where('active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $event, $payload, $context);
        }
    }

    /**
     * Deliver webhook to endpoint
     */
    protected function deliverWebhook(Webhook $webhook, string $event, array $payload, array $context = []): void
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'context' => $context,
            'status' => 'pending',
            'attempts' => 0
        ]);

        // Queue the webhook delivery
        Queue::push(new DeliverWebhook($delivery));
    }

    /**
     * Actually send the webhook HTTP request
     */
    public function sendWebhook(WebhookDelivery $delivery): bool
    {
        $webhook = $delivery->webhook;
        $delivery->increment('attempts');

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'AfricanVLP-Webhooks/1.0',
                'X-Webhook-Event' => $delivery->event,
                'X-Webhook-Delivery' => $delivery->id,
                'X-Webhook-Timestamp' => now()->timestamp
            ];

            // Add signature if secret is configured
            if ($webhook->secret) {
                $signature = $this->generateSignature($delivery->payload, $webhook->secret);
                $headers['X-Webhook-Signature'] = $signature;
            }

            $response = Http::withHeaders($headers)
                ->timeout($webhook->timeout)
                ->withOptions([
                    'verify' => $webhook->verify_ssl
                ])
                ->post($webhook->url, [
                    'event' => $delivery->event,
                    'payload' => $delivery->payload,
                    'context' => $delivery->context,
                    'timestamp' => now()->toISOString(),
                    'delivery_id' => $delivery->id
                ]);

            if ($response->successful()) {
                $delivery->update([
                    'status' => 'delivered',
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                    'delivered_at' => now()
                ]);

                // Update webhook success metrics
                $webhook->increment('successful_deliveries');
                $webhook->update(['last_successful_delivery' => now()]);

                return true;
            } else {
                $this->handleFailedDelivery($delivery, $response->status(), $response->body());
                return false;
            }

        } catch (\Exception $e) {
            $this->handleFailedDelivery($delivery, 0, $e->getMessage());
            return false;
        }
    }

    /**
     * Handle failed webhook delivery
     */
    protected function handleFailedDelivery(WebhookDelivery $delivery, int $statusCode, string $errorMessage): void
    {
        $webhook = $delivery->webhook;

        $delivery->update([
            'status' => 'failed',
            'response_status' => $statusCode,
            'response_body' => $errorMessage,
            'failed_at' => now()
        ]);

        // Update webhook failure metrics
        $webhook->increment('failed_deliveries');
        $webhook->update(['last_failed_delivery' => now()]);

        // Schedule retry if attempts are remaining
        if ($delivery->attempts < $webhook->max_retries) {
            $retryDelay = $this->calculateRetryDelay($delivery->attempts);
            Queue::later($retryDelay, new DeliverWebhook($delivery));
        } else {
            $delivery->update(['status' => 'exhausted']);
            
            // Notify about webhook failures if configured
            $this->notifyWebhookFailure($webhook, $delivery);
        }

        Log::warning('Webhook delivery failed', [
            'webhook_id' => $webhook->id,
            'delivery_id' => $delivery->id,
            'url' => $webhook->url,
            'event' => $delivery->event,
            'attempts' => $delivery->attempts,
            'status_code' => $statusCode,
            'error' => $errorMessage
        ]);
    }

    /**
     * Calculate retry delay based on attempt number
     */
    protected function calculateRetryDelay(int $attempt): int
    {
        $baseDelay = $this->config['retry_delay'] ?? 60;
        return $baseDelay * pow(2, $attempt - 1); // Exponential backoff
    }

    /**
     * Generate webhook signature
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $data = json_encode($payload, JSON_UNESCAPED_SLASHES);
        return 'sha256=' . hash_hmac('sha256', $data, $secret);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate webhook secret
     */
    protected function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get webhook statistics
     */
    public function getWebhookStats(Webhook $webhook): array
    {
        $totalDeliveries = $webhook->deliveries()->count();
        $successfulDeliveries = $webhook->deliveries()->where('status', 'delivered')->count();
        $failedDeliveries = $webhook->deliveries()->where('status', 'failed')->count();
        $exhaustedDeliveries = $webhook->deliveries()->where('status', 'exhausted')->count();

        $successRate = $totalDeliveries > 0 ? round(($successfulDeliveries / $totalDeliveries) * 100, 2) : 0;

        return [
            'total_deliveries' => $totalDeliveries,
            'successful_deliveries' => $successfulDeliveries,
            'failed_deliveries' => $failedDeliveries,
            'exhausted_deliveries' => $exhaustedDeliveries,
            'success_rate' => $successRate,
            'last_successful_delivery' => $webhook->last_successful_delivery?->toISOString(),
            'last_failed_delivery' => $webhook->last_failed_delivery?->toISOString(),
            'average_response_time' => $this->calculateAverageResponseTime($webhook)
        ];
    }

    /**
     * Calculate average response time for webhook
     */
    protected function calculateAverageResponseTime(Webhook $webhook): ?float
    {
        $deliveries = $webhook->deliveries()
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->get();

        if ($deliveries->isEmpty()) {
            return null;
        }

        $totalTime = $deliveries->sum(function ($delivery) {
            return $delivery->created_at->diffInMilliseconds($delivery->delivered_at);
        });

        return round($totalTime / $deliveries->count(), 2);
    }

    /**
     * Notify about webhook failures
     */
    protected function notifyWebhookFailure(Webhook $webhook, WebhookDelivery $delivery): void
    {
        $notificationConfig = config('volunteering.integrations.notifications.webhook_failures');
        
        if (!($notificationConfig['enabled'] ?? false)) {
            return;
        }

        $consecutiveFailures = $webhook->deliveries()
            ->where('status', 'exhausted')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $threshold = $notificationConfig['threshold'] ?? 3;

        if ($consecutiveFailures >= $threshold) {
            // Send notification (implementation would depend on notification system)
            Log::critical('Webhook has exceeded failure threshold', [
                'webhook_id' => $webhook->id,
                'url' => $webhook->url,
                'consecutive_failures' => $consecutiveFailures,
                'threshold' => $threshold
            ]);
        }
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(Webhook $webhook): array
    {
        $testPayload = [
            'event' => 'webhook.test',
            'payload' => [
                'message' => 'This is a test webhook delivery',
                'webhook_id' => $webhook->id,
                'timestamp' => now()->toISOString()
            ],
            'context' => [
                'test' => true
            ]
        ];

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event' => 'webhook.test',
            'payload' => $testPayload['payload'],
            'context' => $testPayload['context'],
            'status' => 'pending',
            'attempts' => 0
        ]);

        $success = $this->sendWebhook($delivery);

        return [
            'success' => $success,
            'delivery_id' => $delivery->id,
            'status' => $delivery->status,
            'response_status' => $delivery->response_status,
            'response_body' => $delivery->response_body,
            'attempts' => $delivery->attempts
        ];
    }

    /**
     * Disable webhook after too many failures
     */
    public function disableWebhookIfNeeded(Webhook $webhook): void
    {
        $recentFailures = $webhook->deliveries()
            ->where('status', 'exhausted')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $failureThreshold = 10; // Disable after 10 exhausted deliveries in 24 hours

        if ($recentFailures >= $failureThreshold) {
            $webhook->update(['active' => false]);
            
            Log::warning('Webhook disabled due to excessive failures', [
                'webhook_id' => $webhook->id,
                'url' => $webhook->url,
                'recent_failures' => $recentFailures
            ]);
        }
    }

    /**
     * Get available webhook events
     */
    public function getAvailableEvents(): array
    {
        return $this->config['events'] ?? [
            'opportunity.created',
            'opportunity.updated',
            'opportunity.deleted',
            'application.submitted',
            'application.reviewed',
            'application.accepted',
            'application.rejected',
            'time_log.submitted',
            'time_log.approved',
            'time_log.rejected',
            'volunteer.assigned',
            'volunteer.completed'
        ];
    }
}