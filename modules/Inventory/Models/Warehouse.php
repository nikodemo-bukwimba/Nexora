<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends InventoryModel
{
    use SoftDeletes;

    protected $fillable = [
        'org_id', 'actor_id', 'name', 'code', 'type',
        'address', 'city', 'country', 'status', 'settings',
    ];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'warehouse_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class, 'warehouse_id');
    }

    public function totalStock(): int
    {
        return $this->batches()->where('status', 'active')->sum('quantity_available');
    }

    public function isActive(): bool { return $this->status === 'active'; }
}
