<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ActorTypeSeeder::class,
            PlatformRoleSeeder::class,
            PlatformTierSeeder::class,
            FeatureFlagSeeder::class,
            PlatformPermissionSeeder::class,
            PlatformRolePermissionSeeder::class,
            OrgApprovalConfigSeeder::class,
        ]);
    }
}
