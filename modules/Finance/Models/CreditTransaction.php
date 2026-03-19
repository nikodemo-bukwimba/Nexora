<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends FinanceModel
{
    // Append-only ledger — no updated_at
    public $timestamps  = false;
    protected $table    = 'credit_transactions';
    protected $fillable = [
        'account_id', 'amount', 'currency',
        'type', 'description', 'ref_type', 'ref_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CreditAccount::class, 'account_id');
    }

    public function isCredit(): bool { return $this->amount > 0; }
    public function isDebit(): bool  { return $this->amount < 0; }
}
