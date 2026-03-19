<?php

namespace Modules\Notifications\Models;

class DeviceToken extends NotificationsModel
{
    protected $table    = 'device_tokens';
    protected $fillable = [
        'actor_id', 'token', 'platform', 'driver',
        'device_name', 'is_active', 'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }
}
