<?php

namespace Modules\Platform\Models;

class ActorType extends PlatformModel
{
    protected $fillable = ['name', 'source', 'module', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
