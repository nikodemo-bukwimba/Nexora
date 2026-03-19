<?php

namespace Modules\Platform\Events;

use Modules\Platform\Models\Organization;

class OrgSuspended extends PlatformEvent
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $suspendedBy
    ) {}

    public function moduleName(): string { return 'platform'; }
    public function eventName(): string  { return 'org.suspended'; }

    public function payload(): array
    {
        return ['org_id' => $this->organization->id, 'suspended_by' => $this->suspendedBy];
    }
}
