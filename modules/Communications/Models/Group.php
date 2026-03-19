<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends CommunicationsModel
{
    protected $fillable = [
        'name', 'description', 'avatar_url', 'created_by',
        'org_id', 'community_id', 'type', 'status',
        'max_participants', 'retention_days',
        'only_admins_can_message', 'only_admins_can_edit_info',
        'last_message_id', 'last_message_at', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'only_admins_can_message'   => 'boolean',
            'only_admins_can_edit_info' => 'boolean',
            'last_message_at'           => 'datetime',
            'settings'                  => 'array',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupParticipant::class, 'group_id')
                    ->where('status', 'active');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GroupMessage::class, 'group_id')->orderBy('created_at');
    }

    public function hasParticipant(string $actorId): bool
    {
        return $this->participants()->where('actor_id', $actorId)->exists();
    }

    public function isAdmin(string $actorId): bool
    {
        return $this->participants()
            ->where('actor_id', $actorId)
            ->whereIn('role', ['admin', 'super_admin'])
            ->exists();
    }
}
