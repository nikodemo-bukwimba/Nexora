<?php

namespace Modules\Platform\Models;

use Modules\Platform\Traits\HasUlid;

class PlatformPermission extends PlatformModel
{
    use HasUlid;

    protected $fillable = ['name', 'group_name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
