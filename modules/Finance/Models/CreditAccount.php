<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditAccount extends FinanceModel
{
    protected $table    = 'credit_accounts';
    protected $fillable = ['actor_id', 'currency', 'status'];

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'account_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Compute current balance from ledger sum.
     * Positive entries = credits, negative entries = debits.
     */
    public function balance(): float
    {
        return (float) $this->transactions()->sum('amount');
    }

    public function isActive(): bool { return $this->status === 'active'; }
    public function isFrozen(): bool { return $this->status === 'frozen'; }
}
