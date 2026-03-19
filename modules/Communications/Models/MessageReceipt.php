<?php

namespace Modules\Communications\Models;

class MessageReceipt extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'message_receipts';
    protected $fillable = ['message_type', 'message_id', 'actor_id', 'delivered_at', 'read_at'];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
        ];
    }
}
