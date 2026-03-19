<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends CommunicationsModel
{
    protected $fillable = [
        'name', 'description', 'avatar_url', 'created_by',
        'org_id', 'status', 'is_public', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'settings'  => 'array',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommunityMember::class, 'community_id')
                    ->where('status', 'active');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(CommunityGroup::class, 'community_id');
    }
}
