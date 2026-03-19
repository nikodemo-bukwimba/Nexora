<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Platform\Traits\HasUlid;

class PlatformRole extends PlatformModel
{
    use HasUlid;

    protected $fillable = ['name', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            PlatformPermission::class,
            'platform_role_permissions',
            'platform_role_id',
            'platform_permission_id'
        );
    }
}
