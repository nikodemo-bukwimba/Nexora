<?php

namespace Modules\Finance\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\Promotion;
use Modules\Finance\Models\PromotionUsage;

interface PromotionServiceInterface
{
    public function create(array $data): Promotion;
    public function validate(string $code, string $actorId, float $orderAmount, string $currency): Promotion;
    public function apply(string $promotionId, string $actorId, float $orderAmount, string $currency, ?string $refType, ?string $refId): PromotionUsage;
    public function calculateDiscount(Promotion $promotion, float $orderAmount): float;
    public function listForOrg(string $orgId, int $perPage): LengthAwarePaginator;
}
