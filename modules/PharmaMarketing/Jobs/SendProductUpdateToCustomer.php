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

    // ── SMS / WhatsApp via MobiShastra Single Push API ─────────
    //
    // Uses sendurl.aspx (single number endpoint) — not sendurlcomma.aspx
    // which is for comma-separated multiple numbers in one call.

    private function sendViaMobiShastra(): void
    {
        $user     = config('pharma_marketing.mobishastra.user');
        $pwd      = config('pharma_marketing.mobishastra.password');
        $senderId = config('pharma_marketing.mobishastra.sender_id', 'BARIKI');

        if (empty($user) || empty($pwd)) {
            $this->markFailed('MobiShastra credentials not configured. Set MOBISHASTRA_USER and MOBISHASTRA_PASSWORD in .env');
            return;
        }

        $mobile = $this->delivery->recipient_address;
        if (empty($mobile)) {
            $this->markFailed('No recipient phone number.');
            return;
        }

        // Strip non-numeric characters; MobiShastra accepts +255XXXXXXXXX format
        // Normalise any Tanzanian number format to 255XXXXXXXXX
        $mobile = preg_replace('/[^0-9]/', '', $mobile); // strip ALL non-digits (removes + sign too)

        if (str_starts_with($mobile, '255') && strlen($mobile) === 12) {
            // Already correct: 255784977960
        } elseif (str_starts_with($mobile, '0') && strlen($mobile) === 10) {
            // Local format: 0784977960 → 255784977960
            $mobile = '255' . substr($mobile, 1);
        } elseif (!str_starts_with($mobile, '255') && strlen($mobile) === 9) {
            // Missing leading zero and country code: 784977960 → 255784977960
            $mobile = '255' . $mobile;
        } else {
            // Unrecognisable format — fail this delivery cleanly
            $this->markFailed("Unrecognised phone format after normalisation: {$mobile}");
            return;
        }

        try {
            // Use sendurl.aspx for single number delivery
            $response = Http::timeout(15)->get('https://mshastra.com/sendurl.aspx', [
                'user'        => $user,
                'pwd'         => $pwd,
                'senderid'    => $senderId,
                'mobileno'    => $mobile,
                'msgtext'     => $message,
                'priority'    => 'High',
                'CountryCode' => 'ALL',
            ]);

            $body = trim($response->body());

            // MobiShastra success: body contains "Send Successful"
            if (str_contains($body, 'Send Successful')) {
                $this->delivery->update([
                    'status'              => 'sent',
                    'external_message_id' => $this->extractMessageId($body),
                    'sent_at'             => now(),
                ]);
                $this->update->increment('sent_count');

                // Mark the parent update as fully sent when all deliveries are done
                $this->checkAndMarkUpdateComplete();

            } else {
                $this->markFailed($body ?: 'Unknown MobiShastra error');
            }

        } catch (\Throwable $e) {
            Log::warning('MobiShastra SMS failed', [
                'delivery_id' => $this->delivery->id,
                'mobile'      => $mobile,
                'error'       => $e->getMessage(),
            ]);
            $this->markFailed($e->getMessage());
        }
    }

    // ── SMS message template ───────────────────────────────────

    private function buildSmsMessage(): string
    {
        $orgName      = $this->resolveOrgName();
        $customerName = $this->customer->name ?? 'Valued Customer';
        $productNames = $this->resolveProductNames();
        $discount     = $this->update->discount_percentage;

        $productLine = !empty($productNames)
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

    private function resolveProductNames(): array
    {
        $ids = $this->update->product_ids ?? [];

        if (empty($ids)) {
            return [];
        }

        return DB::connection('pharma_marketing')
            ->table('pm_products')
            ->whereIn('id', $ids)
            ->pluck('name')
            ->toArray();
    }

    private function resolveOrgName(): string
    {
        return DB::connection('platform')
            ->table('organizations')
            ->where('id', $this->update->org_id)
            ->value('name') ?? 'Bariki Pharmacy';
    }

    // ── In-app delivery ────────────────────────────────────────

    private function markInAppSent(): void
    {
        $this->delivery->update([
            'status'  => 'sent',
            'sent_at' => now(),
        ]);
        $this->update->increment('sent_count');
        $this->checkAndMarkUpdateComplete();
    }

    // ── Mark update as fully sent when all deliveries complete ─

    private function checkAndMarkUpdateComplete(): void
    {
        $total   = $this->update->total_recipients ?? 0;
        $sent    = (int) DB::connection('pharma_marketing')
            ->table('pm_product_update_deliveries')
            ->where('product_update_id', $this->update->id)
            ->where('status', 'sent')
            ->count();
        $failed  = (int) DB::connection('pharma_marketing')
            ->table('pm_product_update_deliveries')
            ->where('product_update_id', $this->update->id)
            ->where('status', 'failed')
            ->count();

        if ($total > 0 && ($sent + $failed) >= $total) {
            $this->update->update([
                'status'       => 'sent',
                'failed_count' => $failed,
            ]);
        }
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