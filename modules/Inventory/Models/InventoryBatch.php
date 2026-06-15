<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends InventoryModel
{
    protected $table    = 'inventory_batches';
    protected $fillable = [
        'warehouse_id', 'product_id', 'variant_id', 'org_id',  // ← variant_id added
        'batch_number', 'sku',
        'quantity_received', 'quantity_available',
        'quantity_reserved', 'quantity_damaged',
        'unit_cost', 'currency', 'status',
        'received_at', 'expires_at', 'best_before_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost'      => 'decimal:4',
            'received_at'    => 'datetime',
            'expires_at'     => 'datetime',
            'best_before_at' => 'datetime',
            'metadata'       => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id')->orderBy('created_at', 'desc');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'batch_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class, 'batch_id');
    }

    // ── Cross-module relationships (Commerce) ──────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Commerce\Models\Product::class, 'product_id', 'id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Commerce\Models\ProductVariant::class, 'variant_id', 'id');
    }

    // ── Helpers ───────────────────────────────────────────────

    public function availableQuantity(): int
    {
        return $this->quantity_available - $this->quantity_reserved;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isNearExpiry(int $days = 30): bool
    {
        return $this->expires_at && $this->expires_at->isBefore(now()->addDays($days));
    }

    public function isActive(): bool    { return $this->status === 'active'; }
    public function isDepleted(): bool  { return $this->quantity_available <= 0; }
}