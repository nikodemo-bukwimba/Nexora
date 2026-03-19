<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class PlatformPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ── Staff management ───────────────────────────────
            ['name' => 'staff.view',              'group_name' => 'staff'],
            ['name' => 'staff.assign',            'group_name' => 'staff'],
            ['name' => 'staff.revoke',            'group_name' => 'staff'],

            // ── Organization management ────────────────────────
            ['name' => 'orgs.view',               'group_name' => 'organizations'],
            ['name' => 'orgs.approve',            'group_name' => 'organizations'],
            ['name' => 'orgs.reject',             'group_name' => 'organizations'],
            ['name' => 'orgs.suspend',            'group_name' => 'organizations'],
            ['name' => 'orgs.reactivate',         'group_name' => 'organizations'],

            // ── User management ────────────────────────────────
            ['name' => 'users.view',              'group_name' => 'users'],
            ['name' => 'users.suspend',           'group_name' => 'users'],
            ['name' => 'users.ban',               'group_name' => 'users'],
            ['name' => 'users.tier.assign',       'group_name' => 'users'],

            // ── Feature flags ──────────────────────────────────
            ['name' => 'flags.view',              'group_name' => 'feature_flags'],
            ['name' => 'flags.toggle',            'group_name' => 'feature_flags'],

            // ── Tiers ──────────────────────────────────────────
            ['name' => 'tiers.view',              'group_name' => 'tiers'],
            ['name' => 'tiers.manage',            'group_name' => 'tiers'],

            // ── Audit log ──────────────────────────────────────
            ['name' => 'audit.view',              'group_name' => 'audit'],
        ];

        foreach ($permissions as $permission) {
            DB::connection('platform')->table('platform_permissions')->insertOrIgnore([
                'id'          => (string) new Ulid(),
                'name'        => $permission['name'],
                'group_name'  => $permission['group_name'],
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
