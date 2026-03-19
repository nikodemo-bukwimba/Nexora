<?php

namespace Modules\Finance\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\Invoice;

interface InvoiceServiceInterface
{
    public function create(array $data): Invoice;
    public function get(string $id): Invoice;
    public function listForActor(string $actorId, array $filters, int $perPage): LengthAwarePaginator;
    public function listForOrg(string $orgId, array $filters, int $perPage): LengthAwarePaginator;
    public function issue(string $id): Invoice;
    public function markPaid(string $id, string $paymentId): Invoice;
    public function cancel(string $id): Invoice;
    public function void(string $id): Invoice;
    public function generateFromSubscription(string $orgId, string $subscriptionId): Invoice;
    public function generateNumber(): string;
}
