<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StopStatusLog extends LogisticsModel
{
    public $timestamps  = false;
    protected $table    = 'lg_stop_status_logs';
    protected $fillable = [
        'stop_id', 'from_status', 'to_status',
        'changed_by', 'latitude', 'longitude', 'notes',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(DeliveryStop::class, 'stop_id');
    }
}
