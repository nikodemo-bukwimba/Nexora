<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends InventoryModel
{
    protected $table    = 'stock_reservations';
    protected $fillable = [
        'batch_id', 'product_id', 'org_id',
        'quantity', 'ref_type', 'ref_id',
        'status', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function isActive(): bool    { return $this->status === 'active'; }
    public function isExpired(): bool   { return $this->expires_at && $this->expires_at->isPast(); }

    /**
     * Find all active reservations tied to a given ref (e.g. an Order
     * that failed to commit). Used by InventoryDeductionService's
     * failure-recovery path and by the stale-reservation sweep job.
     */
    public function scopeForRef($query, string $refType, string $refId)
    {
        return $query->where('ref_type', $refType)
                      ->where('ref_id', $refId)
                      ->where('status', 'active');
    }
}