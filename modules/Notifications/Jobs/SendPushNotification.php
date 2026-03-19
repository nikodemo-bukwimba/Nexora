<?php

namespace Modules\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Models\DeviceToken;
use Modules\Notifications\Models\Notification;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly Notification $notification,
        public readonly DeviceToken  $token
    ) {}

    public function handle(): void
    {
        $driver = $this->token->driver;

        try {
            match ($driver) {
                'fcm'       => $this->sendFcm(),
                'web-push'  => $this->sendWebPush(),
                default     => throw new \RuntimeException("Unsupported push driver: {$driver}"),
            };

            $this->token->update(['last_used_at' => now()]);

            $this->notification->update([
                'status'       => 'delivered',
                'delivered_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Push notification failed", [
                'notification_id' => $this->notification->id,
                'token'           => substr($this->token->token, 0, 20) . '...',
                'error'           => $e->getMessage(),
            ]);

            $this->notification->increment('retry_count');

            if ($this->attempts() >= $this->tries) {
                $this->notification->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    private function sendFcm(): void
    {
        $fcmKey = config('notifications.push.fcm_key');
        if (! $fcmKey) {
            Log::debug('FCM key not configured — push skipped (dev mode)');
            return;
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => "key={$fcmKey}",
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to'           => $this->token->token,
            'notification' => [
                'title' => $this->notification->title,
                'body'  => $this->notification->body,
            ],
            'data'         => array_merge($this->notification->data ?? [], [
                'type'       => $this->notification->type,
                'action_url' => $this->notification->action_url,
                'ref_type'   => $this->notification->ref_type,
                'ref_id'     => $this->notification->ref_id,
            ]),
        ]);

        if (! $response->ok()) {
            throw new \RuntimeException("FCM returned {$response->status()}: {$response->body()}");
        }
    }

    private function sendWebPush(): void
    {
        // Web Push implementation depends on the web-push library
        // Placeholder — add minishlink/web-push to composer for production
        Log::debug('Web Push driver: implementation pending');
    }
}
