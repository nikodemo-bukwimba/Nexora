<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Platform\Traits\HasUlid;

class OrgRoleDelegation extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'parent_org_id', 'child_org_id', 'org_role_id',
        'granted_by', 'granted_at', 'status',
    ];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime'];
    }

    public function parentOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_org_id');
    }

    public function childOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'child_org_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(OrgRole::class, 'org_role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            OrgPermissionDefinition::class,
            'org_delegation_permissions',
            'delegation_id',
            'org_permission_def_id'
        );
    }
}
