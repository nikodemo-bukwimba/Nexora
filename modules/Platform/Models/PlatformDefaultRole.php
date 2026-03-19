<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Platform\Traits\HasUlid;

class PlatformDefaultRole extends PlatformModel
{
    use HasUlid;

    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            OrgPermissionDefinition::class,
            'platform_default_role_permissions',
            'default_role_id',
            'org_permission_def_id'
        );
    }
}
