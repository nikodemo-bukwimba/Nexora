<?php

namespace Modules\PharmaMarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Models\ProductUpdate;
use Modules\PharmaMarketing\Models\ProductUpdateDelivery;

class SendProductUpdateToCustomer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly ProductUpdateDelivery $delivery,
        public readonly ProductUpdate         $update,
        public readonly Customer              $customer,
    ) {}

    public function handle(): void
    {
        try {
            match ($this->delivery->channel) {
                'whatsapp' => $this->sendWhatsApp(),
                'sms'      => $this->sendSms(),
                'in_app'   => $this->sendInApp(),
                default    => throw new \RuntimeException("Unknown channel: {$this->delivery->channel}"),
            };

            $this->delivery->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Product update delivery failed", [
                'delivery_id' => $this->delivery->id,
                'channel'     => $this->delivery->channel,
                'error'       => $e->getMessage(),
            ]);

            $this->delivery->increment('retry_count');

            if ($this->attempts() >= $this->tries) {
                $this->delivery->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    private function sendWhatsApp(): void
    {
        $apiKey = config('pharma_marketing.whatsapp_api_key');
        if (! $apiKey) {
            Log::debug('WhatsApp API key not configured — skipped (dev mode)');
            return;
        }

        $driver = config('pharma_marketing.whatsapp_driver', 'twilio');
        $to     = $this->delivery->recipient_address;
        $body   = "**{$this->update->title}**\n\n{$this->update->body}";

        // Twilio implementation — adapt per driver
        $response = Http::withBasicAuth(
            config('pharma_marketing.whatsapp_api_key'),
            config('pharma_marketing.whatsapp_auth_token')
        )->post("https://api.twilio.com/2010-04-01/Accounts/" . config('pharma_marketing.twilio_account_sid') . "/Messages.json", [
            'From' => 'whatsapp:' . config('pharma_marketing.whatsapp_from'),
            'To'   => 'whatsapp:' . $to,
            'Body' => $body,
        ]);

        if (! $response->ok()) {
            throw new \RuntimeException("WhatsApp delivery failed: {$response->body()}");
        }

        $this->delivery->update(['external_message_id' => $response->json('sid')]);
    }

    private function sendSms(): void
    {
        $apiKey = config('pharma_marketing.sms_api_key');
        if (! $apiKey) {
            Log::debug('SMS API key not configured — skipped (dev mode)');
            return;
        }

        $to   = $this->delivery->recipient_address;
        $body = "{$this->update->title}: {$this->update->body}";

        // Generic SMS — adapt per driver (Twilio, Africastalking, Vonage, etc.)
        $response = Http::withBasicAuth(
            config('pharma_marketing.sms_api_key'),
            config('pharma_marketing.sms_auth_token')
        )->post("https://api.twilio.com/2010-04-01/Accounts/" . config('pharma_marketing.twilio_account_sid') . "/Messages.json", [
            'From' => config('pharma_marketing.sms_from'),
            'To'   => $to,
            'Body' => $body,
        ]);

        if (! $response->ok()) {
            throw new \RuntimeException("SMS delivery failed: {$response->body()}");
        }

        $this->delivery->update(['external_message_id' => $response->json('sid')]);
    }

    private function sendInApp(): void
    {
        // Delegates to Notifications module
        $notifService = app(\Modules\Notifications\Services\NotificationService::class);
        $notifService->send(
            actorId:  $this->customer->assigned_officer_id ?? 'system',
            type:     'product.update',
            title:    $this->update->title,
            body:     $this->update->body,
            refType:  'ProductUpdate',
            refId:    $this->update->id,
        );
    }
}
