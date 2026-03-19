<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupParticipant extends CommunicationsModel
{
    protected $table    = 'group_participants';
    protected $fillable = [
        'group_id', 'actor_id', 'role', 'muted', 'archived',
        'muted_until', 'added_by', 'status', 'left_at',
    ];

    protected function casts(): array
    {
        return [
            'muted'      => 'boolean',
            'archived'   => 'boolean',
            'muted_until' => 'datetime',
            'left_at'    => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
