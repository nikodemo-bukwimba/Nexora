<?php

namespace Modules\Finance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Contracts\Services\PromotionServiceInterface;

class PromotionController extends Controller
{
    public function __construct(protected PromotionServiceInterface $promotions) {}

    /** GET /api/v1/finance/orgs/{orgId}/promotions */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json(
            $this->promotions->listForOrg($orgId, (int) $request->get('per_page', 25))
        );
    }

    /** POST /api/v1/finance/orgs/{orgId}/promotions */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'code'  => ['required', 'string', 'max:50'],
            'name'  => ['required', 'string', 'max:100'],
            'type'  => ['required', 'string', 'in:percentage,fixed_amount'],
            'value' => ['required', 'numeric', 'min:0'],
        ]);

        $promotion = $this->promotions->create(array_merge($request->all(), ['org_id' => $orgId]));
        return response()->json(['message' => 'Promotion created.', 'promotion' => $promotion], 201);
    }

    /** POST /api/v1/finance/promotions/validate */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code'         => ['required', 'string'],
            'actor_id'     => ['required', 'string'],
            'order_amount' => ['required', 'numeric', 'min:0'],
            'currency'     => ['required', 'string', 'size:3'],
        ]);

        $promotion = $this->promotions->validate(
            $request->code, $request->actor_id, $request->order_amount, $request->currency
        );

        $discount = $this->promotions->calculateDiscount($promotion, $request->order_amount);

        return response()->json([
            'valid'            => true,
            'promotion'        => $promotion,
            'discount_amount'  => $discount,
            'final_amount'     => round($request->order_amount - $discount, 4),
        ]);
    }
}
