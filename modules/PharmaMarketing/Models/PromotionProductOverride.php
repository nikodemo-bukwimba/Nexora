<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class PromotionProductOverride extends Model
{
    use HasUlids;

    protected $connection = 'pharma_marketing';
    protected $table      = 'pm_promotion_product_overrides';

    protected $fillable = [
        'product_update_id',
        'variant_id',
        'discount_percentage',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
        ];
    }

    public function productUpdate()
    {
        return $this->belongsTo(ProductUpdate::class, 'product_update_id');
    }
}