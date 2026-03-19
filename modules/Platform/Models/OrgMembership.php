<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class OrgMembership extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'user_id', 'org_id', 'org_role_id',
        'level', 'invited_by', 'status', 'joined_at',
    ];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function orgRole(): BelongsTo
    {
        return $this->belongsTo(OrgRole::class, 'org_role_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(OrgRole::class, 'org_role_id');
    }

    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
