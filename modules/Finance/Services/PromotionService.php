<?php

namespace Modules\Finance\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Contracts\Services\PromotionServiceInterface;
use Modules\Finance\Models\Promotion;
use Modules\Finance\Models\PromotionUsage;

class PromotionService implements PromotionServiceInterface
{
    public function create(array $data): Promotion
    {
        $data['usage_count'] = 0;
        $data['code'] = strtoupper($data['code']);
        return Promotion::create($data);
    }

    public function validate(string $code, string $actorId, float $orderAmount, string $currency): Promotion
    {
        $promotion = Promotion::where('code', strtoupper($code))->first();

        if (! $promotion) {
            throw new \RuntimeException("Promotion code '$code' not found.");
        }

        if (! $promotion->isValid()) {
            throw new \RuntimeException("Promotion code '$code' is not valid or has expired.");
        }

        if ($promotion->min_order_amount && $orderAmount < $promotion->min_order_amount) {
            throw new \RuntimeException("Minimum order amount of {$promotion->min_order_amount} required.");
        }

        if ($promotion->usage_limit_per_actor) {
            $used = $promotion->usageCountForActor($actorId);
            if ($used >= $promotion->usage_limit_per_actor) {
                throw new \RuntimeException("You have already used this promotion code the maximum number of times.");
            }
        }

        return $promotion;
    }

    public function apply(string $promotionId, string $actorId, float $orderAmount, string $currency, ?string $refType, ?string $refId): PromotionUsage
    {
        $promotion = Promotion::findOrFail($promotionId);
        $discount  = $this->calculateDiscount($promotion, $orderAmount);

        // Record usage
        $usage = PromotionUsage::create([
            'promotion_id'    => $promotionId,
            'actor_id'        => $actorId,
            'ref_type'        => $refType,
            'ref_id'          => $refId,
            'discount_applied' => $discount,
            'currency'        => $currency,
        ]);

        // Increment usage count
        $promotion->increment('usage_count');

        return $usage;
    }

    public function calculateDiscount(Promotion $promotion, float $orderAmount): float
    {
        $discount = match ($promotion->type) {
            'percentage'    => $orderAmount * ($promotion->value / 100),
            'fixed_amount'  => min($promotion->value, $orderAmount),
            default         => 0,
        };

        // Apply max discount cap for percentage discounts
        if ($promotion->max_discount_amount && $discount > $promotion->max_discount_amount) {
            $discount = $promotion->max_discount_amount;
        }

        return round($discount, 4);
    }

    public function listForOrg(string $orgId, int $perPage): LengthAwarePaginator
    {
        return Promotion::where('org_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
