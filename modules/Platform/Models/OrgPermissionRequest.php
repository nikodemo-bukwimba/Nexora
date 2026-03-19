<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class OrgPermissionRequest extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'requesting_org_id', 'target_org_id', 'org_role_id',
        'org_permission_def_id', 'reason', 'status',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function requestingOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'requesting_org_id');
    }

    public function targetOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'target_org_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(OrgRole::class, 'org_role_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(OrgPermissionDefinition::class, 'org_permission_def_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
