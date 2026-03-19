<?php

namespace Modules\Notifications\Models;

class NotificationPreference extends NotificationsModel
{
    protected $table    = 'notification_preferences';
    protected $fillable = [
        'actor_id', 'type', 'push_enabled', 'email_enabled', 'sms_enabled',
    ];

    protected function casts(): array
    {
        return [
            'push_enabled'  => 'boolean',
            'email_enabled' => 'boolean',
            'sms_enabled'   => 'boolean',
        ];
    }
}
