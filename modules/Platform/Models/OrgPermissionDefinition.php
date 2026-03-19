<?php

namespace Modules\Platform\Models;

use Modules\Platform\Traits\HasUlid;

class OrgPermissionDefinition extends PlatformModel
{
    use HasUlid;

    protected $table    = 'org_permission_definitions';
    protected $fillable = ['name', 'group_name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
