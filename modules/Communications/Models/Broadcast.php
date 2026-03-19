<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends CommunicationsModel
{
    protected $fillable = ['name', 'owner_actor_id', 'org_id', 'status'];

    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class, 'broadcast_id')
                    ->where('status', 'active');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BroadcastMessage::class, 'broadcast_id')
                    ->orderBy('created_at', 'desc');
    }
}
