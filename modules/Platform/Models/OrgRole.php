<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Platform\Traits\HasUlid;

class OrgRole extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'root_org_id', 'name', 'slug', 'source', 'default_role_id', 'is_system',
    ];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function rootOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'root_org_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            OrgPermissionDefinition::class,
            'org_role_permissions',
            'org_role_id',
            'org_permission_def_id'
        );
    }

    public function defaultRole(): BelongsTo
    {
        return $this->belongsTo(PlatformDefaultRole::class, 'default_role_id');
    }
}