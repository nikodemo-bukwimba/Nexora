<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlert extends InventoryModel
{
    protected $table    = 'stock_alerts';
    protected $fillable = [
        'warehouse_id', 'product_id', 'batch_id', 'org_id',
        'type', 'status', 'threshold', 'current_value',
        'message', 'acknowledged_by', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function isActive(): bool       { return $this->status === 'active'; }
    public function isAcknowledged(): bool { return $this->status === 'acknowledged'; }
    public function isResolved(): bool     { return $this->status === 'resolved'; }
}
