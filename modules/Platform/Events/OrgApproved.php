<?php

namespace Modules\Platform\Events;

use Modules\Platform\Models\Organization;

class OrgApproved extends PlatformEvent
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $approvedBy
    ) {}

    public function moduleName(): string { return 'platform'; }
    public function eventName(): string  { return 'org.approved'; }

    public function payload(): array
    {
        return [
            'org_id'      => $this->organization->id,
            'approved_by' => $this->approvedBy,
            'approved_at' => now()->toISOString(),
        ];
    }
}
