<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends FinanceModel
{
    protected $table    = 'invoice_line_items';
    protected $fillable = [
        'invoice_id', 'description', 'quantity', 'unit_price',
        'subtotal', 'tax_rate', 'tax_amount', 'discount_amount',
        'total', 'currency', 'ref_type', 'ref_id', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'       => 'decimal:4',
            'subtotal'         => 'decimal:4',
            'tax_rate'         => 'decimal:4',
            'tax_amount'       => 'decimal:4',
            'discount_amount'  => 'decimal:4',
            'total'            => 'decimal:4',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
