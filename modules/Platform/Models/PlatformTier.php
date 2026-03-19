<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Platform\Traits\HasUlid;

class PlatformTier extends PlatformModel
{
    use HasUlid;

    protected $fillable = [
        'name', 'description', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ];
    }

    public function userAssignments(): HasMany
    {
        return $this->hasMany(UserTierAssignment::class, 'tier_id');
    }
}
