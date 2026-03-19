<?php

namespace Modules\Platform\Events;

use Modules\Platform\Models\Organization;

class OrgRejected extends PlatformEvent
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $rejectedBy,
        public readonly string $reason
    ) {}

    public function moduleName(): string { return 'platform'; }
    public function eventName(): string  { return 'org.rejected'; }

    public function payload(): array
    {
        return [
            'org_id'      => $this->organization->id,
            'rejected_by' => $this->rejectedBy,
            'reason'      => $this->reason,
        ];
    }
}
