<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

/**
 * Seed the default org configuration so customer app users
 * are automatically added to Barick Pharmacy on registration.
 *
 * The platform.default_org_id flag works as follows:
 *   key         = 'platform.default_org_id'
 *   value       = true  (boolean on/off switch)
 *   description = the root org ULID (01KM3J1485S5T17RXQ6JRWF8JR)
 *
 * Also creates a 'Customer' org role for self-registered users.
 * This role has read-only access: products.view only.
 */
return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        $rootOrgId = '01KM3J1485S5T17RXQ6JRWF8JR'; // Barick Pharmacy root org

        // 1. Seed the feature flag
        DB::connection('platform')->table('platform_feature_flags')->insertOrIgnore([
            'id'          => (string) new Ulid(),
            'key'         => 'platform.default_org_id',
            'value'       => true,
            'description' => $rootOrgId,
            'module'      => 'platform',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 2. Create 'Customer' org role if it doesn't exist
        $existingRole = DB::connection('platform')
            ->table('org_roles')
            ->where('root_org_id', $rootOrgId)
            ->where('slug', 'customer')
            ->first();

        if (! $existingRole) {
            $roleId = (string) new Ulid();

            DB::connection('platform')->table('org_roles')->insert([
                'id'          => $roleId,
                'root_org_id' => $rootOrgId,
                'name'        => 'Customer',
                'slug'        => 'customer',
                'source'      => 'custom',
                'is_system'   => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Grant read-only permissions to the customer role
            $readPermissions = [
                'products.view',
                'inventory.view',
                'orders.view',
                'orders.create',
            ];

            foreach ($readPermissions as $permName) {
                $perm = DB::connection('platform')
                    ->table('org_permission_definitions')
                    ->where('name', $permName)
                    ->first();

                if ($perm) {
                    DB::connection('platform')
                        ->table('org_role_permissions')
                        ->insertOrIgnore([
                            'org_role_id'          => $roleId,
                            'org_permission_def_id' => $perm->id,
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::connection('platform')
            ->table('platform_feature_flags')
            ->where('key', 'platform.default_org_id')
            ->delete();

        DB::connection('platform')
            ->table('org_roles')
            ->where('root_org_id', '01KM3J1485S5T17RXQ6JRWF8JR')
            ->where('slug', 'customer')
            ->delete();
    }
};