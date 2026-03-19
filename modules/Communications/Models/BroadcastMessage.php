<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastMessage extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'broadcast_messages';
    protected $fillable = [
        'broadcast_id', 'sender_actor_id', 'content', 'content_type',
        'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class, 'broadcast_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class, 'message_id')
                    ->where('message_type', 'BroadcastMessage');
    }
}
