<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends InventoryModel
{
    // Immutable ledger — append-only, no updated_at
    public $timestamps  = false;
    protected $table    = 'stock_movements';
    protected $fillable = [
        'batch_id', 'warehouse_id', 'product_id', 'org_id',
        'type', 'quantity', 'quantity_before', 'quantity_after',
        'ref_type', 'ref_id', 'performed_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function isInbound(): bool  { return $this->quantity > 0; }
    public function isOutbound(): bool { return $this->quantity < 0; }
}
