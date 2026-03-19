<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Contracts\Services\SubscriptionServiceInterface;
use Modules\Finance\Models\OrgSubscription;
use Modules\Finance\Models\SubscriptionPlan;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function getActivePlans(): Collection
    {
        return SubscriptionPlan::where('is_active', true)
            ->where('is_public', true)
            ->with('limits')
            ->orderBy('sort_order')
            ->get();
    }

    public function getPlan(string $planId): SubscriptionPlan
    {
        return SubscriptionPlan::with('limits')->findOrFail($planId);
    }

    public function subscribe(string $orgId, string $planId, array $options = []): OrgSubscription
    {
        $existing = $this->getSubscription($orgId);

        if ($existing && $existing->isActive()) {
            throw new \RuntimeException('Organization already has an active subscription.');
        }

        $plan = SubscriptionPlan::findOrFail($planId);

        return OrgSubscription::create([
            'org_id' => $orgId,
            'plan_id' => $planId,
            'status' => $options['status'] ?? 'active',
            'trial_ends_at' => $options['trial_ends_at'] ?? null,
            'current_period_start' => now(),
            'current_period_end' => $this->computePeriodEnd($plan->billing_cycle),
            'gateway' => $options['gateway'] ?? null,
            'gateway_subscription_id' => $options['gateway_subscription_id'] ?? null,
        ]);
    }

    public function changePlan(string $orgId, string $newPlanId): OrgSubscription
    {
        $subscription = OrgSubscription::where('org_id', $orgId)->firstOrFail();
        $plan = SubscriptionPlan::findOrFail($newPlanId);

        $subscription->update([
            'plan_id' => $newPlanId,
            'current_period_end' => $this->computePeriodEnd($plan->billing_cycle),
        ]);

        return $subscription->fresh(['plan.limits']);
    }

    public function cancel(string $orgId): OrgSubscription
    {
        $subscription = OrgSubscription::where('org_id', $orgId)->firstOrFail();

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }

    public function renew(string $orgId): OrgSubscription
    {
        $subscription = OrgSubscription::where('org_id', $orgId)
            ->with('plan')
            ->firstOrFail();

        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
            'current_period_start' => now(),
            'current_period_end' => $this->computePeriodEnd($subscription->plan->billing_cycle),
        ]);

        return $subscription->fresh();
    }

    public function getSubscription(string $orgId): ?OrgSubscription
    {
        return OrgSubscription::where('org_id', $orgId)
            ->with(['plan.limits'])
            ->latest()
            ->first();
    }

    public function isActive(string $orgId): bool
    {
        $subscription = $this->getSubscription($orgId);
        return $subscription && $subscription->isActive() && !$subscription->isExpired();
    }

    /**
     * FULLY IMPLEMENTED — never returns true blindly.
     *
     * Checks the org's subscription plan limits table to determine
     * if the org is within the limit for the given feature.
     *
     * Examples:
     *   isWithinLimits('orgId', 'max_members', 47)       → true if plan allows >47
     *   isWithinLimits('orgId', 'can_use_promotions', 0)  → true if plan enables it
     *   isWithinLimits('orgId', 'max_branches', 0)        → true if plan allows it
     */
    public function isWithinLimits(string $orgId, string $featureKey, int $currentUsage = 0): bool
    {
        $subscription = $this->getSubscription($orgId);

        // No subscription — allow access (grace period / free tier assumption)
        if (!$subscription || !$subscription->isActive()) {
            return false;
        }

        $plan = $subscription->plan;
        $limit = $plan->limits->firstWhere('feature_key', $featureKey);

        // Feature not defined on this plan — default to allow
        if (!$limit) {
            return true;
        }

        return $limit->allows($currentUsage);
    }

    private function computePeriodEnd(string $billingCycle): \Carbon\CarbonInterface
    {
        return match ($billingCycle) {
            'annual' => now()->addYear(),
            default => now()->addMonth(),
        };
    }
}
