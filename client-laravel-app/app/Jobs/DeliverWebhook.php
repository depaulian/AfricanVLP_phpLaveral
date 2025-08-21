<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WebhookDelivery $delivery;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1; // We handle retries manually in the webhook service

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(WebhookDelivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        try {
            $webhookService->sendWebhook($this->delivery);
        } catch (\Exception $e) {
            Log::error('Webhook delivery job failed', [
                'delivery_id' => $this->delivery->id,
                'webhook_id' => $this->delivery->webhook_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark delivery as failed if it's not already handled
            if ($this->delivery->status === 'pending') {
                $this->delivery->update([
                    'status' => 'failed',
                    'response_body' => 'Job execution failed: ' . $e->getMessage(),
                    'failed_at' => now()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook delivery job permanently failed', [
            'delivery_id' => $this->delivery->id,
            'webhook_id' => $this->delivery->webhook_id,
            'error' => $exception->getMessage()
        ]);

        // Update delivery status if not already updated
        if ($this->delivery->status === 'pending') {
            $this->delivery->update([
                'status' => 'exhausted',
                'response_body' => 'Job permanently failed: ' . $exception->getMessage(),
                'failed_at' => now()
            ]);
        }
    }
}