<?php

namespace Modules\Finance\Contracts\Services;

use Modules\Finance\Models\CommissionConfig;
use Modules\Finance\Models\CommissionRecord;

interface CommissionServiceInterface
{
    public function getActiveConfig(): CommissionConfig;
    public function calculate(float $transactionAmount, string $currency): array;
    public function record(string $paymentId, string $actorId, float $transactionAmount, string $currency): CommissionRecord;
    public function collect(string $recordId): CommissionRecord;
    public function waive(string $recordId): CommissionRecord;
}
