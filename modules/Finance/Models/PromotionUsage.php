<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionUsage extends FinanceModel
{
    public $timestamps  = false;
    protected $table    = 'promotion_usages';
    protected $fillable = [
        'promotion_id', 'actor_id', 'ref_type', 'ref_id',
        'discount_applied', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'discount_applied' => 'decimal:4',
            'used_at'          => 'datetime',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
