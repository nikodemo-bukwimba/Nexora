<?php

namespace Modules\PharmaMarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Models\ProductUpdate;
use Modules\PharmaMarketing\Models\ProductUpdateDelivery;

class SendProductUpdateToCustomer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ProductUpdateDelivery $delivery,
        public readonly ProductUpdate         $update,
        public readonly Customer              $customer
    ) {}

    public function handle(): void
    {
        match ($this->delivery->channel) {
            'sms', 'whatsapp' => $this->sendViaMobiShastra(),
            'in_app'          => $this->markInAppSent(),
            default           => $this->markFailed('Unknown channel: ' . $this->delivery->channel),
        };
    }

    // ── SMS / WhatsApp via MobiShastra Push API ────────────────

    private function sendViaMobiShastra(): void
    {
        $user     = config('pharma_marketing.mobishastra.user');
        $pwd      = config('pharma_marketing.mobishastra.password');
        $senderId = config('pharma_marketing.mobishastra.sender_id', 'BARICK');

        if (!$user || !$pwd) {
            $this->markFailed('MobiShastra credentials not configured.');
            return;
        }

        $mobile = $this->delivery->recipient_address;
        if (!$mobile) {
            $this->markFailed('No recipient phone number.');
            return;
        }

        $message = $this->buildSmsMessage();

        try {
            $response = Http::timeout(15)->get('https://mshastra.com/sendurlcomma.aspx', [
                'user'        => $user,
                'pwd'         => $pwd,
                'senderid'    => $senderId,
                'mobileno'    => $mobile,
                'msgtext'     => $message,
                'priority'    => 'High',
                'CountryCode' => 'ALL',
            ]);

            $body = trim($response->body());

            // MobiShastra returns "Send Successful" in body on success
            if (str_contains($body, 'Send Successful')) {
                $this->delivery->update([
                    'status'              => 'sent',
                    'external_message_id' => $this->extractMessageId($body),
                    'sent_at'             => now(),
                ]);
                $this->update->increment('sent_count');
            } else {
                $this->markFailed($body ?: 'Unknown MobiShastra error');
            }
        } catch (\Throwable $e) {
            Log::warning('MobiShastra SMS failed', [
                'delivery_id' => $this->delivery->id,
                'error'       => $e->getMessage(),
            ]);
            $this->markFailed($e->getMessage());
        }
    }

    // ── Build fixed SMS template ───────────────────────────────
    //
    //  Dear {customer_name},
    //  {OrgName} has an update for you:
    //
    //  Products: Product A, Product B, Product C
    //  Discount: 20% OFF          ← only when discount_percentage is set
    //
    //  Visit us or contact your representative.

    private function buildSmsMessage(): string
    {
        $orgName      = $this->resolveOrgName();
        $customerName = $this->customer->name ?? 'Valued Customer';
        $productNames = $this->resolveProductNames();
        $discount     = $this->update->discount_percentage;

        $productLine  = !empty($productNames)
            ? 'Products: ' . implode(', ', $productNames)
            : 'New products available';

        $discountLine = $discount
            ? "\nDiscount: {$discount}% OFF"
            : '';

        return "Dear {$customerName},\n"
             . "{$orgName} has an update for you:\n\n"
             . "{$productLine}"
             . "{$discountLine}\n\n"
             . "Visit us or contact your representative.";
    }

    // ── Resolve product names from product_ids ─────────────────

    private function resolveProductNames(): array
    {
        $ids = $this->update->product_ids ?? [];

        if (empty($ids)) {
            return [];
        }

        return DB::connection('commerce')
            ->table('products')
            ->whereIn('id', $ids)
            ->pluck('name')
            ->toArray();
    }

    // ── Resolve org name ───────────────────────────────────────

    private function resolveOrgName(): string
    {
        return DB::connection('platform')
            ->table('organizations')
            ->where('id', $this->update->org_id)
            ->value('name') ?? 'Barick Pharmacy';
    }

    // ── In-app delivery (no external call needed) ──────────────

    private function markInAppSent(): void
    {
        $this->delivery->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);
        $this->update->increment('sent_count');
    }

    // ── Helpers ────────────────────────────────────────────────

    private function extractMessageId(string $body): ?string
    {
        // MobiShastra body format: "MsgID: 123456 Send Successful"
        if (preg_match('/MsgID[:\s]+(\d+)/i', $body, $m)) {
            return $m[1];
        }
        return null;
    }

    private function markFailed(string $reason): void
    {
        $this->delivery->update([
            'status'         => 'failed',
            'failure_reason' => $reason,
            'retry_count'    => $this->delivery->retry_count + 1,
        ]);
        $this->update->increment('failed_count');

        Log::warning('Product update delivery failed', [
            'delivery_id' => $this->delivery->id,
            'reason'      => $reason,
        ]);
    }
}