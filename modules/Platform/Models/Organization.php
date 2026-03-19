<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Platform\Traits\HasUlid;

class Organization extends PlatformModel
{
    use HasUlid, SoftDeletes;

    protected $fillable = [
        'actor_id', 'parent_id', 'root_org_id', 'path',
        'depth', 'name', 'slug', 'type', 'status', 'settings',
        'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'settings'    => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    public function root(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'root_org_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrgMembership::class, 'org_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isRoot(): bool
    {
        return $this->type === 'root';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
