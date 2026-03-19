<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class OrgApprovalConfigSeeder extends Seeder
{
    /**
     * Seed the feature flag that controls which platform permission
     * is required to approve organizations.
     * Configurable: platform admin can change this via the flags API.
     */
    public function run(): void
    {
        DB::connection('platform')->table('platform_feature_flags')->insertOrIgnore([
            'id'          => (string) new Ulid(),
            'key'         => 'orgs.approval_required_permission',
            'value'       => true,
            'description' => 'Permission required to approve organizations. Default: orgs.approve',
            'module'      => 'organizations',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
