<?php

namespace Modules\Finance\Services;

use Modules\Finance\Contracts\Services\CommissionServiceInterface;
use Modules\Finance\Models\CommissionConfig;
use Modules\Finance\Models\CommissionRecord;

class CommissionService implements CommissionServiceInterface
{
    public function getActiveConfig(): CommissionConfig
    {
        return CommissionConfig::where('is_active', true)
            ->where('is_default', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            })
            ->firstOrFail();
    }

    public function calculate(float $transactionAmount, string $currency): array
    {
        $config           = $this->getActiveConfig();
        $commissionAmount = round($transactionAmount * $config->rate, 4);
        $netAmount        = $transactionAmount - $commissionAmount;

        return [
            'config_id'         => $config->id,
            'rate'              => $config->rate,
            'transaction_amount' => $transactionAmount,
            'commission_amount'  => $commissionAmount,
            'net_amount'        => $netAmount,
            'currency'          => $currency,
        ];
    }

    public function record(string $paymentId, string $actorId, float $transactionAmount, string $currency): CommissionRecord
    {
        $calculation = $this->calculate($transactionAmount, $currency);

        return CommissionRecord::create([
            'commission_config_id' => $calculation['config_id'],
            'payment_id'           => $paymentId,
            'actor_id'             => $actorId,
            'transaction_amount'   => $transactionAmount,
            'commission_rate'      => $calculation['rate'],
            'commission_amount'    => $calculation['commission_amount'],
            'currency'             => $currency,
            'status'               => 'pending',
        ]);
    }

    public function collect(string $recordId): CommissionRecord
    {
        $record = CommissionRecord::findOrFail($recordId);
        $record->update(['status' => 'collected', 'collected_at' => now()]);
        return $record->fresh();
    }

    public function waive(string $recordId): CommissionRecord
    {
        $record = CommissionRecord::findOrFail($recordId);
        $record->update(['status' => 'waived']);
        return $record->fresh();
    }
}
