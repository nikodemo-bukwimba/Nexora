<?php

namespace Modules\Platform\Events;

use Modules\Platform\Models\Organization;

class OrgCreated extends PlatformEvent
{
    public function __construct(public readonly Organization $organization) {}

    public function moduleName(): string { return 'platform'; }
    public function eventName(): string  { return 'org.created'; }

    public function payload(): array
    {
        return [
            'org_id'     => $this->organization->id,
            'name'       => $this->organization->name,
            'slug'       => $this->organization->slug,
            'type'       => $this->organization->type,
            'root_org_id' => $this->organization->root_org_id,
        ];
    }
}
