<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupMessage extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'group_messages';
    protected $fillable = [
        'group_id', 'sender_actor_id', 'content', 'content_type',
        'reply_to_id', 'forwarded_from_id', 'forwarded',
        'latitude', 'longitude',
        'deleted_for_everyone', 'is_system_message', 'system_event',
    ];

    protected function casts(): array
    {
        return [
            'forwarded'            => 'boolean',
            'deleted_for_everyone' => 'boolean',
            'is_system_message'    => 'boolean',
            'created_at'           => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(GroupMessage::class, 'reply_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class, 'message_id')
                    ->where('message_type', 'GroupMessage');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class, 'message_id')
                    ->where('message_type', 'GroupMessage');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(MessageReceipt::class, 'message_id')
                    ->where('message_type', 'GroupMessage');
    }
}
