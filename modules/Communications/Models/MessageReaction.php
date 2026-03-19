<?php

namespace Modules\Communications\Models;

class MessageReaction extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'message_reactions';
    protected $fillable = ['message_type', 'message_id', 'actor_id', 'emoji'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
