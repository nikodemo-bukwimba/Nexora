<?php

namespace Modules\Finance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Contracts\Services\CommissionServiceInterface;
use Modules\Finance\Contracts\Services\PaymentServiceInterface;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentServiceInterface    $payments,
        protected CommissionServiceInterface $commissions,
    ) {}

    /** POST /api/v1/finance/payments */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'payer_actor_id' => ['required', 'string'],
            'payee_actor_id' => ['required', 'string'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'currency'       => ['required', 'string', 'size:3'],
            'method'         => ['nullable', 'string'],
        ]);

        $payment = $this->payments->create($request->all());

        // Auto-record commission on payment creation
        $commissionRecord = $this->commissions->record(
            $payment->id,
            $request->payer_actor_id,
            $request->amount,
            $request->currency
        );

        return response()->json([
            'message'    => 'Payment created.',
            'payment'    => $payment,
            'commission' => $commissionRecord,
        ], 201);
    }

    /** GET /api/v1/finance/payments/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->payments->get($id));
    }

    /** GET /api/v1/finance/actors/{actorId}/payments */
    public function forActor(Request $request, string $actorId): JsonResponse
    {
        return response()->json(
            $this->payments->listForActor($actorId, $request->only(['status', 'method']), (int) $request->get('per_page', 25))
        );
    }

    /** POST /api/v1/finance/payments/{id}/complete */
    public function complete(Request $request, string $id): JsonResponse
    {
        $payment = $this->payments->markCompleted($id, $request->all());
        return response()->json(['message' => 'Payment completed.', 'payment' => $payment]);
    }

    /** POST /api/v1/finance/payments/{id}/refund */
    public function refund(Request $request, string $id): JsonResponse
    {
        $payment = $this->payments->refund($id, $request->amount ?? null);
        return response()->json(['message' => 'Payment refunded.', 'payment' => $payment]);
    }
}
