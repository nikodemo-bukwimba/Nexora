<?php

namespace Modules\Notifications\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Models\DeviceToken;
use Modules\Notifications\Models\Notification;
use Modules\Notifications\Models\NotificationPreference;
use Symfony\Component\Uid\Ulid;

class NotificationService
{
    /**
     * Send a notification to an actor via all their active devices.
     * Respects actor preferences for each notification type.
     */
    public function send(
        string  $actorId,
        string  $type,
        string  $title,
        string  $body,
        ?string $actionUrl = null,
        ?string $refType   = null,
        ?string $refId     = null,
        ?array  $data      = null
    ): Notification {
        // Check actor preference for this notification type
        if (! $this->isEnabledForActor($actorId, $type, 'push')) {
            // Create a record but skip sending
            return $this->createRecord($actorId, $type, $title, $body, 'push', $actionUrl, $refType, $refId, $data, 'skipped');
        }

        $notification = $this->createRecord($actorId, $type, $title, $body, 'push', $actionUrl, $refType, $refId, $data);

        // Dispatch push to all active devices
        $this->dispatchPush($notification, $actorId);

        return $notification->fresh();
    }

    /**
     * Send a notification to multiple actors (bulk).
     */
    public function sendToMany(array $actorIds, string $type, string $title, string $body, array $options = []): void
    {
        foreach ($actorIds as $actorId) {
            $this->send($actorId, $type, $title, $body, $options['action_url'] ?? null, $options['ref_type'] ?? null, $options['ref_id'] ?? null, $options['data'] ?? null);
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markRead(string $notificationId, string $actorId): Notification
    {
        $notification = Notification::where('id', $notificationId)
            ->where('actor_id', $actorId)
            ->firstOrFail();

        $notification->update(['status' => 'read', 'read_at' => now()]);

        return $notification->fresh();
    }

    /**
     * Mark all unread notifications for an actor as read.
     */
    public function markAllRead(string $actorId): void
    {
        Notification::where('actor_id', $actorId)
            ->whereNull('read_at')
            ->update(['status' => 'read', 'read_at' => now()]);
    }

    /**
     * List notifications for an actor, newest first.
     */
    public function list(string $actorId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Notification::where('actor_id', $actorId)
            ->when(isset($filters['type']),   fn($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Count unread notifications.
     */
    public function unreadCount(string $actorId): int
    {
        return Notification::where('actor_id', $actorId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Register a device token for push notifications.
     */
    public function registerDevice(string $actorId, string $token, string $platform, string $driver = 'fcm', ?string $deviceName = null): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'actor_id'    => $actorId,
                'platform'    => $platform,
                'driver'      => $driver,
                'device_name' => $deviceName,
                'is_active'   => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Deactivate a device token (logout/unsubscribe).
     */
    public function deregisterDevice(string $token): void
    {
        DeviceToken::where('token', $token)->update(['is_active' => false]);
    }

    /**
     * Get or create actor preference for a notification type.
     */
    public function getPreferences(string $actorId): array
    {
        return NotificationPreference::where('actor_id', $actorId)->get()->toArray();
    }

    /**
     * Update actor preference for a specific notification type.
     */
    public function updatePreference(string $actorId, string $type, array $channels): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            ['actor_id' => $actorId, 'type' => $type],
            [
                'push_enabled'  => $channels['push']  ?? true,
                'email_enabled' => $channels['email'] ?? false,
                'sms_enabled'   => $channels['sms']   ?? false,
            ]
        );
    }

    // ── Private helpers ────────────────────────────────────────

    private function isEnabledForActor(string $actorId, string $type, string $channel): bool
    {
        $pref = NotificationPreference::where('actor_id', $actorId)
            ->where('type', $type)
            ->first();

        if (! $pref) return true; // default: all on

        return match ($channel) {
            'push'  => (bool) $pref->push_enabled,
            'email' => (bool) $pref->email_enabled,
            'sms'   => (bool) $pref->sms_enabled,
            default => true,
        };
    }

    private function createRecord(
        string $actorId, string $type, string $title, string $body,
        string $channel, ?string $actionUrl, ?string $refType,
        ?string $refId, ?array $data, string $status = 'pending'
    ): Notification {
        return Notification::create([
            'actor_id'   => $actorId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'channel'    => $channel,
            'action_url' => $actionUrl,
            'ref_type'   => $refType,
            'ref_id'     => $refId,
            'data'       => $data,
            'status'     => $status,
        ]);
    }

    private function dispatchPush(Notification $notification, string $actorId): void
    {
        $tokens = DeviceToken::where('actor_id', $actorId)
            ->where('is_active', true)
            ->get();

        if ($tokens->isEmpty()) {
            $notification->update(['status' => 'failed', 'failure_reason' => 'No active device tokens.']);
            return;
        }

        // Dispatch to queue for actual push delivery
        foreach ($tokens as $token) {
            \Modules\Notifications\Jobs\SendPushNotification::dispatch($notification, $token);
        }

        $notification->update(['status' => 'sent', 'sent_at' => now()]);
    }
}
