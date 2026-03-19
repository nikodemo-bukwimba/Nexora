<?php

namespace Modules\Communications\Models;

class BroadcastRecipient extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'broadcast_recipients';
    protected $fillable = ['broadcast_id', 'actor_id', 'status'];
}
