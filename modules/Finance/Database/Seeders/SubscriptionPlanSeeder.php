<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'          => 'free',
                'description'   => 'Free plan. Basic access for small teams.',
                'price'         => 0.0000,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'is_active'     => true,
                'is_public'     => true,
                'sort_order'    => 0,
                'limits'        => [
                    ['feature_key' => 'max_members',          'feature_group' => 'members',      'limit_value' => ['value' => 5]],
                    ['feature_key' => 'max_branches',         'feature_group' => 'branches',     'limit_value' => ['value' => 1]],
                    ['feature_key' => 'max_orders_per_month',  'feature_group' => 'orders',       'limit_value' => ['value' => 50]],
                    ['feature_key' => 'can_use_credit',        'feature_group' => 'finance',      'limit_value' => ['enabled' => true]],
                    ['feature_key' => 'can_use_promotions',    'feature_group' => 'finance',      'limit_value' => ['enabled' => false]],
                    ['feature_key' => 'max_storage_mb',        'feature_group' => 'storage',      'limit_value' => ['value' => 500]],
                ],
            ],
            [
                'name'          => 'professional',
                'description'   => 'Professional plan. For growing organizations.',
                'price'         => 49.0000,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'is_active'     => true,
                'is_public'     => true,
                'sort_order'    => 1,
                'limits'        => [
                    ['feature_key' => 'max_members',          'feature_group' => 'members',      'limit_value' => ['value' => 50]],
                    ['feature_key' => 'max_branches',         'feature_group' => 'branches',     'limit_value' => ['value' => 10]],
                    ['feature_key' => 'max_orders_per_month',  'feature_group' => 'orders',       'limit_value' => ['value' => 1000]],
                    ['feature_key' => 'can_use_credit',        'feature_group' => 'finance',      'limit_value' => ['enabled' => true]],
                    ['feature_key' => 'can_use_promotions',    'feature_group' => 'finance',      'limit_value' => ['enabled' => true]],
                    ['feature_key' => 'max_storage_mb',        'feature_group' => 'storage',      'limit_value' => ['value' => 10000]],
                ],
            ],
            [
                'name'          => 'enterprise',
                'description'   => 'Enterprise plan. Unlimited scale.',
                'price'         => 199.0000,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'is_active'     => true,
                'is_public'     => true,
                'sort_order'    => 2,
                'limits'        => [
                    ['feature_key' => 'max_members',          'feature_group' => 'members',      'limit_value' => ['value' => -1]],
                    ['feature_key' => 'max_branches',         'feature_group' => 'branches',     'limit_value' => ['value' => -1]],
                    ['feature_key' => 'max_orders_per_month',  'feature_group' => 'orders',       'limit_value' => ['value' => -1]],
                    ['feature_key' => 'can_use_credit',        'feature_group' => 'finance',      'limit_value' => ['enabled' => true]],
                    ['feature_key' => 'can_use_promotions',    'feature_group' => 'finance',      'limit_value' => ['enabled' => true]],
                    ['feature_key' => 'max_storage_mb',        'feature_group' => 'storage',      'limit_value' => ['value' => -1]],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $limits = $planData['limits'];
            unset($planData['limits']);

            $planId = (string) new Ulid();

            DB::connection('finance')->table('subscription_plans')->insertOrIgnore(array_merge(
                $planData,
                ['id' => $planId, 'created_at' => now(), 'updated_at' => now()]
            ));

            // Re-fetch in case it already existed
            $plan = DB::connection('finance')->table('subscription_plans')
                ->where('name', $planData['name'])->first();

            foreach ($limits as $limit) {
                DB::connection('finance')->table('subscription_plan_limits')->insertOrIgnore([
                    'id'            => (string) new Ulid(),
                    'plan_id'       => $plan->id,
                    'feature_key'   => $limit['feature_key'],
                    'feature_group' => $limit['feature_group'],
                    'limit_value'   => json_encode($limit['limit_value']),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
}
