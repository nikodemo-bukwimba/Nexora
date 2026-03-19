<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class OrgInvitation extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'org_id', 'org_role_id', 'level', 'email',
        'token', 'invited_by', 'status', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(OrgRole::class, 'org_role_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
