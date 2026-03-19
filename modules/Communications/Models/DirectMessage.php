<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DirectMessage extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'direct_messages';
    protected $fillable = [
        'conversation_id', 'sender_actor_id', 'content', 'content_type',
        'reply_to_id', 'forwarded_from_id', 'forwarded',
        'latitude', 'longitude',
        'deleted_for_sender', 'deleted_for_recipient', 'deleted_for_everyone',
        'status', 'delivered_at', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'forwarded'             => 'boolean',
            'deleted_for_sender'    => 'boolean',
            'deleted_for_recipient' => 'boolean',
            'deleted_for_everyone'  => 'boolean',
            'created_at'            => 'datetime',
            'delivered_at'          => 'datetime',
            'read_at'               => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DirectConversation::class, 'conversation_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(DirectMessage::class, 'reply_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class, 'message_id')
                    ->where('message_type', 'DirectMessage');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class, 'message_id')
                    ->where('message_type', 'DirectMessage');
    }
}
