<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class PlatformRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin',    'description' => 'Full platform access. All permissions.'],
            ['name' => 'support_agent',  'description' => 'Read access to orgs and users for support.'],
            ['name' => 'billing_admin',  'description' => 'Manage subscriptions, invoices, and payments.'],
            ['name' => 'content_admin',  'description' => 'Manage platform content and announcements.'],
        ];

        foreach ($roles as $role) {
            DB::connection('platform')->table('platform_roles')->insertOrIgnore([
                'id'          => (string) new Ulid(),
                'name'        => $role['name'],
                'description' => $role['description'],
                'is_system'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
