<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Map of role name => permissions it gets
        $rolePermissions = [
            'super_admin' => [
                // Gets everything
                'staff.view', 'staff.assign', 'staff.revoke',
                'orgs.view', 'orgs.approve', 'orgs.reject', 'orgs.suspend', 'orgs.reactivate',
                'users.view', 'users.suspend', 'users.ban', 'users.tier.assign',
                'flags.view', 'flags.toggle',
                'tiers.view', 'tiers.manage',
                'audit.view',
            ],
            'support_agent' => [
                'orgs.view', 'users.view', 'audit.view',
            ],
            'billing_admin' => [
                'orgs.view', 'users.view', 'users.tier.assign',
                'tiers.view', 'tiers.manage', 'audit.view',
            ],
            'content_admin' => [
                'orgs.view', 'flags.view', 'flags.toggle',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = DB::connection('platform')
                ->table('platform_roles')
                ->where('name', $roleName)
                ->first();

            if (! $role) continue;

            foreach ($permissionNames as $permName) {
                $permission = DB::connection('platform')
                    ->table('platform_permissions')
                    ->where('name', $permName)
                    ->first();

                if (! $permission) continue;

                DB::connection('platform')
                    ->table('platform_role_permissions')
                    ->insertOrIgnore([
                        'platform_role_id'       => $role->id,
                        'platform_permission_id' => $permission->id,
                    ]);
            }
        }
    }
}
