<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

return new class extends Migration
{
    protected $connection = 'platform';

    /**
     * Seed the core org permission definitions.
     * These are the permissions that can ever be assigned to org roles.
     * Only platform admin can add to this list.
     */
    public function up(): void
    {
        $permissions = [
            // Members
            ['name' => 'members.view',           'group_name' => 'members'],
            ['name' => 'members.invite',         'group_name' => 'members'],
            ['name' => 'members.remove',         'group_name' => 'members'],
            ['name' => 'members.update',         'group_name' => 'members'],

            // Roles
            ['name' => 'roles.view',             'group_name' => 'roles'],
            ['name' => 'roles.manage',           'group_name' => 'roles'],

            // Branches
            ['name' => 'branches.view',          'group_name' => 'branches'],
            ['name' => 'branches.create',        'group_name' => 'branches'],
            ['name' => 'branches.update',        'group_name' => 'branches'],

            // Org settings
            ['name' => 'org.settings.view',      'group_name' => 'org_settings'],
            ['name' => 'org.settings.update',    'group_name' => 'org_settings'],

            // Delegations
            ['name' => 'delegations.manage',     'group_name' => 'delegations'],

            // Orders (foundation module — pre-registered for Commerce)
            ['name' => 'orders.view',            'group_name' => 'orders'],
            ['name' => 'orders.create',          'group_name' => 'orders'],
            ['name' => 'orders.approve',         'group_name' => 'orders'],
            ['name' => 'orders.cancel',          'group_name' => 'orders'],

            // Inventory (foundation module — pre-registered for Inventory)
            ['name' => 'inventory.view',         'group_name' => 'inventory'],
            ['name' => 'inventory.create',       'group_name' => 'inventory'],
            ['name' => 'inventory.update',       'group_name' => 'inventory'],
            ['name' => 'inventory.delete',       'group_name' => 'inventory'],

            // Finance (foundation module — pre-registered for Finance)
            ['name' => 'invoices.view',          'group_name' => 'finance'],
            ['name' => 'invoices.manage',        'group_name' => 'finance'],
            ['name' => 'payments.view',          'group_name' => 'finance'],
            ['name' => 'payments.manage',        'group_name' => 'finance'],

            // Communications (foundation module)
            ['name' => 'conversations.view',     'group_name' => 'communications'],
            ['name' => 'conversations.create',   'group_name' => 'communications'],
        ];

        foreach ($permissions as $perm) {
            DB::connection('platform')->table('org_permission_definitions')->insertOrIgnore([
                'id'         => (string) new Ulid(),
                'name'       => $perm['name'],
                'group_name' => $perm['group_name'],
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::connection('platform')->table('org_permission_definitions')->truncate();
    }
};
