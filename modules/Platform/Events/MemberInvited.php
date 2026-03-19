<?php

namespace Modules\Platform\Events;

use Modules\Platform\Models\OrgInvitation;

class MemberInvited extends PlatformEvent
{
    public function __construct(public readonly OrgInvitation $invitation) {}

    public function moduleName(): string { return 'platform'; }
    public function eventName(): string  { return 'member.invited'; }

    public function payload(): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'org_id'        => $this->invitation->org_id,
            'email'         => $this->invitation->email,
            'role_id'       => $this->invitation->org_role_id,
            'level'         => $this->invitation->level,
            'invited_by'    => $this->invitation->invited_by,
            'expires_at'    => $this->invitation->expires_at->toISOString(),
        ];
    }
}
