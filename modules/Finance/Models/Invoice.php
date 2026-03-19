<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends FinanceModel
{
    protected $fillable = [
        'invoice_number', 'issuer_actor_id', 'recipient_actor_id',
        'org_id', 'source_type', 'source_id', 'status',
        'subtotal', 'tax_amount', 'discount_amount', 'total',
        'currency', 'issued_at', 'due_at', 'paid_at', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'         => 'decimal:4',
            'tax_amount'       => 'decimal:4',
            'discount_amount'  => 'decimal:4',
            'total'            => 'decimal:4',
            'issued_at'        => 'datetime',
            'due_at'           => 'datetime',
            'paid_at'          => 'datetime',
            'metadata'         => 'array',
        ];
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class, 'invoice_id')
                    ->orderBy('sort_order');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'invoice_id');
    }

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isPaid(): bool       { return $this->status === 'paid'; }
    public function isOverdue(): bool    { return $this->status === 'sent' && $this->due_at?->isPast(); }
}
