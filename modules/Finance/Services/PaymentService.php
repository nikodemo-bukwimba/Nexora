<?php

namespace Modules\Finance\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Contracts\Services\PaymentServiceInterface;
use Modules\Finance\Models\Payment;

class PaymentService implements PaymentServiceInterface
{
    public function create(array $data): Payment
    {
        $data['status'] = $data['status'] ?? 'pending';
        return Payment::create($data);
    }

    public function get(string $id): Payment
    {
        return Payment::with(['invoice', 'commissionRecord'])->findOrFail($id);
    }

    public function listForActor(string $actorId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Payment::where(function ($q) use ($actorId) {
                $q->where('payer_actor_id', $actorId)
                  ->orWhere('payee_actor_id', $actorId);
            })
            ->when(isset($filters['status']),  fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['method']),  fn($q) => $q->where('method', $filters['method']))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function markCompleted(string $id, array $gatewayData = []): Payment
    {
        $payment = Payment::findOrFail($id);

        $payment->update([
            'status'            => 'completed',
            'paid_at'           => now(),
            'gateway_status'    => $gatewayData['gateway_status'] ?? 'succeeded',
            'gateway_payment_id' => $gatewayData['gateway_payment_id'] ?? $payment->gateway_payment_id,
            'gateway_fee'       => $gatewayData['gateway_fee'] ?? 0,
            'net_amount'        => $payment->amount - ($gatewayData['gateway_fee'] ?? 0),
        ]);

        return $payment->fresh();
    }

    public function markFailed(string $id, string $reason): Payment
    {
        $payment = Payment::findOrFail($id);
        $payment->update(['status' => 'failed', 'failure_reason' => $reason]);
        return $payment->fresh();
    }

    public function refund(string $id, ?float $amount = null): Payment
    {
        $payment = Payment::findOrFail($id);

        if (! $payment->isCompleted()) {
            throw new \RuntimeException("Only completed payments can be refunded.");
        }

        $refundAmount = $amount ?? $payment->amount;
        $status       = $refundAmount >= $payment->amount ? 'refunded' : 'partially_refunded';

        $payment->update(['status' => $status]);

        return $payment->fresh();
    }
}
