<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Platform\Traits\HasUlid;

class OrgScopeGrant extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'membership_id', 'scope_type', 'granted_by',
        'granted_at', 'status', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrgMembership::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'org_scope_grant_branches',
            'scope_grant_id',
            'org_id'
        );
    }
}
