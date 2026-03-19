<?php

namespace Modules\Platform\Events;

use Modules\Platform\Models\User;

class UserRegistered extends PlatformEvent
{
    public function __construct(public readonly User $user) {}

    public function moduleName(): string { return 'platform'; }
    public function eventName(): string  { return 'user.registered'; }

    public function payload(): array
    {
        return [
            'user_id'  => $this->user->id,
            'username' => $this->user->username,
            'email'    => $this->user->email,
            'actor_id' => $this->user->actor_id,
        ];
    }
}
