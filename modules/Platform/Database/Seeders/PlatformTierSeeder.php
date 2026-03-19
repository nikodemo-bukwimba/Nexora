<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class PlatformTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'free',       'description' => 'Free tier. Basic platform access.',         'is_default' => true,  'sort_order' => 0],
            ['name' => 'premium',    'description' => 'Premium tier. Enhanced feature access.',    'is_default' => false, 'sort_order' => 1],
            ['name' => 'enterprise', 'description' => 'Enterprise tier. Full platform access.',    'is_default' => false, 'sort_order' => 2],
        ];

        foreach ($tiers as $tier) {
            DB::connection('platform')->table('platform_tiers')->insertOrIgnore([
                'id'          => (string) new Ulid(),
                'name'        => $tier['name'],
                'description' => $tier['description'],
                'is_default'  => $tier['is_default'],
                'is_active'   => true,
                'sort_order'  => $tier['sort_order'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
