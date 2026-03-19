<?php

namespace Modules\Finance\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\Payment;

interface PaymentServiceInterface
{
    public function create(array $data): Payment;
    public function get(string $id): Payment;
    public function listForActor(string $actorId, array $filters, int $perPage): LengthAwarePaginator;
    public function markCompleted(string $id, array $gatewayData = []): Payment;
    public function markFailed(string $id, string $reason): Payment;
    public function refund(string $id, ?float $amount = null): Payment;
}
