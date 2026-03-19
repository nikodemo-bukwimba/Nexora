<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class AuditLog extends PlatformModel
{
    use HasUlid;

    public $timestamps  = false;
    protected $fillable = [
        'module', 'action', 'actor_id', 'subject_type',
        'subject_id', 'old_values', 'new_values',
        'metadata', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}
