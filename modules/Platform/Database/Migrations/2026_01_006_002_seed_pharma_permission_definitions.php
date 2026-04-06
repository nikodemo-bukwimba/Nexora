<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

return new class extends Migration
{
    protected $connection = 'platform';

    /**
     * Seed pharma-specific org permission definitions.
     * These extend the core 26 permissions with Barick Pharma module permissions.
     */
    public function up(): void
    {
        $permissions = [
            // Products
            ['name' => 'products.view',       'group_name' => 'products'],
            ['name' => 'products.create',     'group_name' => 'products'],
            ['name' => 'products.update',     'group_name' => 'products'],
            ['name' => 'products.delete',     'group_name' => 'products'],

            // Categories
            ['name' => 'categories.view',     'group_name' => 'categories'],
            ['name' => 'categories.create',   'group_name' => 'categories'],
            ['name' => 'categories.update',   'group_name' => 'categories'],
            ['name' => 'categories.delete',   'group_name' => 'categories'],

            // Customers
            ['name' => 'customers.view',      'group_name' => 'customers'],
            ['name' => 'customers.create',    'group_name' => 'customers'],
            ['name' => 'customers.update',    'group_name' => 'customers'],
            ['name' => 'customers.delete',    'group_name' => 'customers'],

            // Officers
            ['name' => 'officers.view',       'group_name' => 'officers'],
            ['name' => 'officers.create',     'group_name' => 'officers'],
            ['name' => 'officers.update',     'group_name' => 'officers'],
            ['name' => 'officers.delete',     'group_name' => 'officers'],

            // Field Visits
            ['name' => 'visits.view',         'group_name' => 'visits'],
            ['name' => 'visits.create',       'group_name' => 'visits'],
            ['name' => 'visits.review',       'group_name' => 'visits'],
            ['name' => 'visits.accept',       'group_name' => 'visits'],
            ['name' => 'visits.flag',         'group_name' => 'visits'],

            // Daily Reports
            ['name' => 'reports.view',        'group_name' => 'reports'],
            ['name' => 'reports.create',      'group_name' => 'reports'],
            ['name' => 'reports.accept',      'group_name' => 'reports'],
            ['name' => 'reports.deny',        'group_name' => 'reports'],
            ['name' => 'reports.export',      'group_name' => 'reports'],

            // Weekly Plans
            ['name' => 'weeklyplans.view',    'group_name' => 'weekly_plans'],
            ['name' => 'weeklyplans.create',  'group_name' => 'weekly_plans'],
            ['name' => 'weeklyplans.update',  'group_name' => 'weekly_plans'],
            ['name' => 'weeklyplans.accept',  'group_name' => 'weekly_plans'],
            ['name' => 'weeklyplans.deny',    'group_name' => 'weekly_plans'],
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
        $names = [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'officers.view', 'officers.create', 'officers.update', 'officers.delete',
            'visits.view', 'visits.create', 'visits.review', 'visits.accept', 'visits.flag',
            'reports.view', 'reports.create', 'reports.accept', 'reports.deny', 'reports.export',
            'weeklyplans.view', 'weeklyplans.create', 'weeklyplans.update', 'weeklyplans.accept', 'weeklyplans.deny',
        ];

        DB::connection('platform')->table('org_permission_definitions')
            ->whereIn('name', $names)
            ->delete();
    }
};