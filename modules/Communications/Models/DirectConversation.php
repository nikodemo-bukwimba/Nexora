<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectConversation extends CommunicationsModel
{
    protected $table    = 'direct_conversations';
    protected $fillable = [
        'initiator_actor_id', 'recipient_actor_id', 'last_message_id',
        'last_message_at', 'initiator_archived', 'recipient_archived',
        'initiator_muted', 'recipient_muted', 'retention_days', 'status',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at'    => 'datetime',
            'initiator_archived' => 'boolean',
            'recipient_archived' => 'boolean',
            'initiator_muted'    => 'boolean',
            'recipient_muted'    => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function isBlockedBy(string $actorId): bool
    {
        return $this->status === 'blocked';
    }
}
