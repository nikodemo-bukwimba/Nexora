<?php

namespace Modules\Finance\Contracts\Services;

use Modules\Finance\Models\OrgSubscription;
use Modules\Finance\Models\SubscriptionPlan;
use Illuminate\Support\Collection;

interface SubscriptionServiceInterface
{
    public function getActivePlans(): Collection;
    public function getPlan(string $planId): SubscriptionPlan;
    public function subscribe(string $orgId, string $planId, array $options = []): OrgSubscription;
    public function changePlan(string $orgId, string $newPlanId): OrgSubscription;
    public function cancel(string $orgId): OrgSubscription;
    public function renew(string $orgId): OrgSubscription;
    public function getSubscription(string $orgId): ?OrgSubscription;
    public function isActive(string $orgId): bool;

    /**
     * Check if an org's subscription allows a specific feature/usage.
     * This is the critical method — fully implemented, never stubbed.
     *
     * @param string $orgId
     * @param string $featureKey  e.g. 'max_members', 'can_use_promotions'
     * @param int    $currentUsage  Current usage count (for numeric limits)
     */
    public function isWithinLimits(string $orgId, string $featureKey, int $currentUsage = 0): bool;
}
