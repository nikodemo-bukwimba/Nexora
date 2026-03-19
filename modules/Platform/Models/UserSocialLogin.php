<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class UserSocialLogin extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'user_id', 'provider', 'provider_id',
        'access_token', 'refresh_token', 'expires_at',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
