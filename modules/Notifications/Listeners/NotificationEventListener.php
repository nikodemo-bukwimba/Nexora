<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\Services\NotificationService;
use Modules\Notifications\Services\WorkflowService;
use Modules\Platform\Events\PlatformEvent;

/**
 * Listens to all PlatformEvents dispatched via EventBus
 * and routes them to the notification + workflow engine.
 *
 * Registered in CommunicationsServiceProvider or via EventServiceProvider.
 */
class NotificationEventListener
{
    public function __construct(
        protected NotificationService $notifications,
        protected WorkflowService     $workflows,
    ) {}

    /**
     * Map platform event names to notification handlers.
     * Each handler sends the appropriate notification(s).
     */
    public function handle(PlatformEvent $event): void
    {
        $name    = $event->fullEventName();
        $payload = $event->payload();

        try {
            match (true) {
                str_starts_with($name, 'platform.org.approved')   => $this->onOrgApproved($payload),
                str_starts_with($name, 'platform.member.invited')  => $this->onMemberInvited($payload),
                default => null,
            };

            // Always attempt workflow trigger
            $this->workflows->triggerByEvent($name, $payload);

        } catch (\Throwable $e) {
            Log::error("NotificationEventListener failed for {$name}: " . $e->getMessage());
        }
    }

    // ── Notification handlers ──────────────────────────────────

    private function onOrgApproved(array $payload): void
    {
        // The org creator gets notified — need org owner actor_id from payload
        // Payload: org_id, approved_by, approved_at
        // We resolve org owner via Platform module
        $org = \Modules\Platform\Models\Organization::with('memberships')->find($payload['org_id']);
        if (! $org) return;

        $owner = $org->memberships()
            ->whereHas('orgRole', fn($q) => $q->where('is_system', true))
            ->where('level', 100)
            ->first();

        if (! $owner) return;

        $user = \Modules\Platform\Models\User::find($owner->user_id);
        if (! $user) return;

        $this->notifications->send(
            actorId:  $user->actor_id,
            type:     'org.approved',
            title:    'Organization Approved',
            body:     "Your organization has been approved and is now active.",
            refType:  'Organization',
            refId:    $payload['org_id'],
        );
    }

    private function onMemberInvited(array $payload): void
    {
        // Payload: invitation_id, org_id, email, invited_by, expires_at
        $user = \Modules\Platform\Models\User::where('email', $payload['email'])->first();
        if (! $user) return;

        $org = \Modules\Platform\Models\Organization::find($payload['org_id']);

        $this->notifications->send(
            actorId:  $user->actor_id,
            type:     'org.invitation',
            title:    'Organization Invitation',
            body:     "You have been invited to join {$org?->name}.",
            refType:  'OrgInvitation',
            refId:    $payload['invitation_id'],
        );
    }
}
