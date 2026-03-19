<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class OrgScopeRequest extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'membership_id', 'requested_scope', 'target_org_ids',
        'reason', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_org_ids' => 'array',
            'reviewed_at'    => 'datetime',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrgMembership::class);
    }
}
