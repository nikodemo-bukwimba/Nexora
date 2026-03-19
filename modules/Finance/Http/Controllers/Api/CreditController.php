<?php

namespace Modules\Finance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Contracts\Services\CreditServiceInterface;

class CreditController extends Controller
{
    public function __construct(protected CreditServiceInterface $credit) {}

    /** GET /api/v1/finance/actors/{actorId}/credit */
    public function show(string $actorId): JsonResponse
    {
        $account = $this->credit->getOrCreateAccount($actorId);
        $balance = $this->credit->getBalance($actorId);

        return response()->json([
            'account' => $account,
            'balance' => $balance,
        ]);
    }

    /** POST /api/v1/finance/actors/{actorId}/credit/topup */
    public function topup(Request $request, string $actorId): JsonResponse
    {
        $request->validate([
            'amount'   => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $tx = $this->credit->topup($actorId, $request->amount, $request->currency, $request->ref_type ?? null, $request->ref_id ?? null);

        return response()->json([
            'message'     => 'Credit topped up.',
            'transaction' => $tx,
            'new_balance' => $this->credit->getBalance($actorId),
        ], 201);
    }

    /** GET /api/v1/finance/actors/{actorId}/credit/ledger */
    public function ledger(Request $request, string $actorId): JsonResponse
    {
        return response()->json(
            $this->credit->getLedger($actorId, (int) $request->get('per_page', 25))
        );
    }
}
