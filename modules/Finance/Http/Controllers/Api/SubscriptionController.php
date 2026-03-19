<?php

namespace Modules\Finance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Contracts\Services\SubscriptionServiceInterface;

class SubscriptionController extends Controller
{
    public function __construct(protected SubscriptionServiceInterface $subscriptions) {}

    /** GET /api/v1/finance/plans */
    public function plans(): JsonResponse
    {
        return response()->json($this->subscriptions->getActivePlans());
    }

    /** GET /api/v1/finance/orgs/{orgId}/subscription */
    public function show(string $orgId): JsonResponse
    {
        $sub = $this->subscriptions->getSubscription($orgId);
        if (! $sub) return response()->json(['message' => 'No subscription found.'], 404);
        return response()->json($sub);
    }

    /** POST /api/v1/finance/orgs/{orgId}/subscription */
    public function subscribe(Request $request, string $orgId): JsonResponse
    {
        $request->validate(['plan_id' => ['required', 'string', 'exists:finance.subscription_plans,id']]);
        $sub = $this->subscriptions->subscribe($orgId, $request->plan_id);
        return response()->json(['message' => 'Subscription created.', 'subscription' => $sub], 201);
    }

    /** PATCH /api/v1/finance/orgs/{orgId}/subscription/plan */
    public function changePlan(Request $request, string $orgId): JsonResponse
    {
        $request->validate(['plan_id' => ['required', 'string', 'exists:finance.subscription_plans,id']]);
        $sub = $this->subscriptions->changePlan($orgId, $request->plan_id);
        return response()->json(['message' => 'Plan changed.', 'subscription' => $sub]);
    }

    /** POST /api/v1/finance/orgs/{orgId}/subscription/cancel */
    public function cancel(string $orgId): JsonResponse
    {
        $sub = $this->subscriptions->cancel($orgId);
        return response()->json(['message' => 'Subscription cancelled.', 'subscription' => $sub]);
    }

    /** POST /api/v1/finance/orgs/{orgId}/subscription/renew */
    public function renew(string $orgId): JsonResponse
    {
        $sub = $this->subscriptions->renew($orgId);
        return response()->json(['message' => 'Subscription renewed.', 'subscription' => $sub]);
    }

    /** GET /api/v1/finance/orgs/{orgId}/subscription/check/{featureKey} */
    public function checkLimit(Request $request, string $orgId, string $featureKey): JsonResponse
    {
        $currentUsage = (int) $request->get('usage', 0);
        $allowed      = $this->subscriptions->isWithinLimits($orgId, $featureKey, $currentUsage);

        return response()->json([
            'org_id'        => $orgId,
            'feature_key'   => $featureKey,
            'current_usage' => $currentUsage,
            'allowed'       => $allowed,
        ]);
    }
}
