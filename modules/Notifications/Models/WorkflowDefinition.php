<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends NotificationsModel
{
    protected $table    = 'workflow_definitions';
    protected $fillable = [
        'org_id', 'name', 'description', 'trigger_event',
        'module', 'steps', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps'     => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class, 'workflow_definition_id');
    }
}
