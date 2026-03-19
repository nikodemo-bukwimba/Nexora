<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Platform\Traits\HasUlid;

class Actor extends PlatformModel
{
    use HasUlid, SoftDeletes;

    protected $connection = 'platform';

    protected $fillable = [
        'display_name',
        'avatar_url',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function typeAssignments(): HasMany
    {
        return $this->hasMany(ActorTypeAssignment::class);
    }

    public function types(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            ActorType::class,
            'actor_type_assignments',
            'actor_id',
            'actor_type_id'
        )->withPivot('assigned_at', 'assigned_by');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(ActorRelationship::class);
    }

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class);
    }
}
