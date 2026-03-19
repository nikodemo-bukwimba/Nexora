<?php

namespace Modules\Communications\Models;

class ActorPresence extends CommunicationsModel
{
    public $timestamps    = false;
    public $incrementing  = false;
    protected $primaryKey = 'actor_id';
    protected $table      = 'actor_presence';
    protected $fillable   = ['actor_id', 'is_online', 'last_seen_at', 'hide_last_seen'];

    protected function casts(): array
    {
        return [
            'is_online'      => 'boolean',
            'hide_last_seen' => 'boolean',
            'last_seen_at'   => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }
}
