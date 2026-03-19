<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends NotificationsModel
{
    protected $table    = 'workflow_runs';
    protected $fillable = [
        'workflow_definition_id', 'trigger_event', 'trigger_payload',
        'status', 'current_step', 'context',
        'failure_reason', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'trigger_payload' => 'array',
            'context'         => 'array',
            'started_at'      => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function stepLogs(): HasMany
    {
        return $this->hasMany(WorkflowStepLog::class, 'run_id')->orderBy('step_index');
    }

    public function isRunning(): bool    { return $this->status === 'running'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }
}
