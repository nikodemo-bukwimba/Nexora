<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class UserTierAssignment extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'user_id', 'tier_id', 'assigned_by',
        'starts_at', 'expires_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'  => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(PlatformTier::class, 'tier_id');
    }
}
