<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Models\User;

class BranchVariantPriceOverride extends CommerceModel
{
    protected $table = 'branch_variant_price_overrides';

    protected $fillable = [
        'org_id',
        'variant_id',
        'price',
        'currency',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}