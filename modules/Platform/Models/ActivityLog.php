<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use HasUlids, SoftDeletes;

    protected $connection = 'platform';
    protected $table      = 'activity_logs';

    protected $fillable = [
        'org_id', 'actor_id', 'actor_name', 'actor_role',
        'action', 'entity_type', 'entity_id',
        'entity_snapshot', 'ip_address', 'user_agent', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_snapshot' => 'array',
            'occurred_at'     => 'datetime',
        ];
    }

    // ── Scopes ──────────────────────────────────────────────

    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    public function scopeForEntity($query, string $type, string $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }
}