<?php

namespace Modules\Finance\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\CreditAccount;
use Modules\Finance\Models\CreditTransaction;

interface CreditServiceInterface
{
    public function getOrCreateAccount(string $actorId, string $currency = 'USD'): CreditAccount;
    public function getBalance(string $actorId): float;
    public function topup(string $actorId, float $amount, string $currency, ?string $refType, ?string $refId): CreditTransaction;
    public function spend(string $actorId, float $amount, string $currency, string $description, ?string $refType, ?string $refId): CreditTransaction;
    public function earn(string $actorId, float $amount, string $currency, string $description, ?string $refType, ?string $refId): CreditTransaction;
    public function refund(string $actorId, float $amount, string $currency, string $description, ?string $refType, ?string $refId): CreditTransaction;
    public function hasEnough(string $actorId, float $amount): bool;
    public function getLedger(string $actorId, int $perPage): LengthAwarePaginator;
}
