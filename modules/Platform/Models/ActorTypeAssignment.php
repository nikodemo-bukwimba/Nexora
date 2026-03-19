<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActorTypeAssignment extends PlatformModel
{
    public $incrementing = false;
    public $timestamps   = false;
    protected $primaryKey = null;

    protected $fillable = ['actor_id', 'actor_type_id', 'assigned_at', 'assigned_by'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function actorType(): BelongsTo
    {
        return $this->belongsTo(ActorType::class);
    }
}
