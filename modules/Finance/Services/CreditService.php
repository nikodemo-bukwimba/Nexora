<?php

namespace Modules\Finance\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Contracts\Services\CreditServiceInterface;
use Modules\Finance\Models\CreditAccount;
use Modules\Finance\Models\CreditTransaction;

class CreditService implements CreditServiceInterface
{
    public function getOrCreateAccount(string $actorId, string $currency = 'USD'): CreditAccount
    {
        return CreditAccount::firstOrCreate(
            ['actor_id' => $actorId],
            ['currency' => $currency, 'status' => 'active']
        );
    }

    public function getBalance(string $actorId): float
    {
        $account = CreditAccount::where('actor_id', $actorId)->first();
        return $account ? $account->balance() : 0.0;
    }

    public function topup(string $actorId, float $amount, string $currency, ?string $refType, ?string $refId): CreditTransaction
    {
        if ($amount <= 0) throw new \InvalidArgumentException('Topup amount must be positive.');

        $minTopup = config('finance.credit.min_topup_amount', 1.00);
        $maxTopup = config('finance.credit.max_topup_amount', 10000.00);

        if ($amount < $minTopup) throw new \InvalidArgumentException("Minimum topup amount is {$minTopup}.");
        if ($amount > $maxTopup) throw new \InvalidArgumentException("Maximum topup amount is {$maxTopup}.");

        $account = $this->getOrCreateAccount($actorId, $currency);

        return CreditTransaction::create([
            'account_id'  => $account->id,
            'amount'      => $amount,       // positive = credit
            'currency'    => $currency,
            'type'        => 'topup',
            'description' => "Credit topup",
            'ref_type'    => $refType,
            'ref_id'      => $refId,
        ]);
    }

    public function spend(string $actorId, float $amount, string $currency, string $description, ?string $refType, ?string $refId): CreditTransaction
    {
        if ($amount <= 0) throw new \InvalidArgumentException('Spend amount must be positive.');
        if (! $this->hasEnough($actorId, $amount)) {
            throw new \RuntimeException('Insufficient credit balance.');
        }

        $account = $this->getOrCreateAccount($actorId, $currency);

        return CreditTransaction::create([
            'account_id'  => $account->id,
            'amount'      => -$amount,      // negative = debit
            'currency'    => $currency,
            'type'        => 'spent',
            'description' => $description,
            'ref_type'    => $refType,
            'ref_id'      => $refId,
        ]);
    }

    public function earn(string $actorId, float $amount, string $currency, string $description, ?string $refType, ?string $refId): CreditTransaction
    {
        $account = $this->getOrCreateAccount($actorId, $currency);

        return CreditTransaction::create([
            'account_id'  => $account->id,
            'amount'      => $amount,       // positive = credit
            'currency'    => $currency,
            'type'        => 'earned',
            'description' => $description,
            'ref_type'    => $refType,
            'ref_id'      => $refId,
        ]);
    }

    public function refund(string $actorId, float $amount, string $currency, string $description, ?string $refType, ?string $refId): CreditTransaction
    {
        $account = $this->getOrCreateAccount($actorId, $currency);

        return CreditTransaction::create([
            'account_id'  => $account->id,
            'amount'      => $amount,       // positive = refund back to credit
            'currency'    => $currency,
            'type'        => 'refunded',
            'description' => $description,
            'ref_type'    => $refType,
            'ref_id'      => $refId,
        ]);
    }

    public function hasEnough(string $actorId, float $amount): bool
    {
        return $this->getBalance($actorId) >= $amount;
    }

    public function getLedger(string $actorId, int $perPage): LengthAwarePaginator
    {
        $account = CreditAccount::where('actor_id', $actorId)->first();

        if (! $account) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return CreditTransaction::where('account_id', $account->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
