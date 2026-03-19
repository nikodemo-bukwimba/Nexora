<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActorRelationship extends PlatformModel
{
    protected $fillable = [
        'actor_id', 'related_actor_id', 'relationship_type',
        'source_module', 'source_event', 'direction',
        'status', 'metadata', 'initiated_at', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'     => 'array',
            'initiated_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function relatedActor(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'related_actor_id');
    }
}
