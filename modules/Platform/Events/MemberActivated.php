<?php
namespace Modules\Platform\Events;

use Modules\Platform\Models\OrgMembership;

class MemberActivated extends PlatformEvent
{
    public function __construct(public readonly OrgMembership $membership) {}

    public function moduleName(): string { return 'platform'; }
    public function eventName(): string  { return 'member.activated'; }

    public function payload(): array
    {
        return [
            'membership_id' => $this->membership->id,
            'user_id'       => $this->membership->user_id,
            'org_id'        => $this->membership->org_id,
            'role_id'       => $this->membership->org_role_id,
        ];
    }
}