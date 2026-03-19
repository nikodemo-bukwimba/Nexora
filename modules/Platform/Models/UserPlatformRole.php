<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlatformRole extends PlatformModel
{
    public $incrementing  = false;
    public $timestamps    = false;
    protected $primaryKey = null;
    protected $fillable   = ['user_id', 'platform_role_id', 'granted_by', 'granted_at'];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platformRole(): BelongsTo
    {
        return $this->belongsTo(PlatformRole::class, 'platform_role_id');
    }
}
