<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        $defaultRoles = [
            'org_admin' => [
                'description' => 'Full access within the org node.',
                'permissions' => [
                    'members.view','members.invite','members.remove','members.update',
                    'roles.view','roles.manage',
                    'branches.view','branches.create','branches.update',
                    'org.settings.view','org.settings.update',
                    'delegations.manage',
                    'orders.view','orders.create','orders.approve','orders.cancel',
                    'inventory.view','inventory.create','inventory.update','inventory.delete',
                    'invoices.view','invoices.manage','payments.view','payments.manage',
                    'conversations.view','conversations.create',
                ],
            ],
            'manager' => [
                'description' => 'Manages day-to-day operations.',
                'permissions' => [
                    'members.view','members.invite',
                    'branches.view',
                    'orders.view','orders.create','orders.approve',
                    'inventory.view','inventory.create','inventory.update',
                    'invoices.view','payments.view',
                    'conversations.view','conversations.create',
                ],
            ],
            'staff' => [
                'description' => 'Standard operational access.',
                'permissions' => [
                    'members.view',
                    'orders.view','orders.create',
                    'inventory.view',
                    'invoices.view',
                    'conversations.view','conversations.create',
                ],
            ],
            'viewer' => [
                'description' => 'Read-only access.',
                'permissions' => [
                    'members.view',
                    'orders.view',
                    'inventory.view',
                    'invoices.view','payments.view',
                    'conversations.view',
                ],
            ],
        ];

        foreach ($defaultRoles as $roleName => $config) {
            $existing = DB::connection('platform')
                ->table('platform_default_roles')
                ->where('name', $roleName)
                ->first();

            if (! $existing) {
                $roleId = (string) new Ulid();
                DB::connection('platform')->table('platform_default_roles')->insert([
                    'id'          => $roleId,
                    'name'        => $roleName,
                    'description' => $config['description'],
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } else {
                $roleId = $existing->id;
            }

            foreach ($config['permissions'] as $permName) {
                $perm = DB::connection('platform')
                    ->table('org_permission_definitions')
                    ->where('name', $permName)
                    ->first();

                if ($perm) {
                    DB::connection('platform')
                        ->table('platform_default_role_permissions')
                        ->insertOrIgnore([
                            'default_role_id'       => $roleId,
                            'org_permission_def_id' => $perm->id,
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::connection('platform')->table('platform_default_role_permissions')->truncate();
        DB::connection('platform')->table('platform_default_roles')->truncate();
    }
};
