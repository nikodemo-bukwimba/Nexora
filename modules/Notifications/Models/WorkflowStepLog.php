<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepLog extends NotificationsModel
{
    public $timestamps  = false;
    protected $table    = 'workflow_step_logs';
    protected $fillable = [
        'run_id', 'step_index', 'step_type', 'step_name',
        'status', 'input', 'output', 'error',
        'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'input'        => 'array',
            'output'       => 'array',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
            'created_at'   => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class, 'run_id');
    }
}
