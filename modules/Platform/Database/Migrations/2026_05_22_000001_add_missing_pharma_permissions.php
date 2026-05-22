<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

return new class extends Migration
{
    protected $connection = 'platform';

    /**
     * Add missing pharma permissions not included in the original seed.
     * Safe to run alongside existing migrations — insertOrIgnore prevents duplicates.
     */
    public function up(): void
    {
        $permissions = [
            // Visits — missing CRUD
            ['name' => 'visits.update',                 'group_name' => 'visits'],
            ['name' => 'visits.delete',                 'group_name' => 'visits'],

            // Daily Reports — missing CRUD
            ['name' => 'reports.update',                'group_name' => 'reports'],
            ['name' => 'reports.delete',                'group_name' => 'reports'],

            // Weekly Plans — missing CRUD
            ['name' => 'weeklyplans.update',            'group_name' => 'weekly_plans'],
            ['name' => 'weeklyplans.delete',            'group_name' => 'weekly_plans'],

            // Promotions — completely missing
            ['name' => 'promotions.view',               'group_name' => 'promotions'],
            ['name' => 'promotions.create',             'group_name' => 'promotions'],
            ['name' => 'promotions.update',             'group_name' => 'promotions'],
            ['name' => 'promotions.delete',             'group_name' => 'promotions'],

            // Notifications — completely missing
            ['name' => 'notifications.view',            'group_name' => 'notifications'],
            ['name' => 'notifications.create',          'group_name' => 'notifications'],
            ['name' => 'notifications.update',          'group_name' => 'notifications'],
            ['name' => 'notifications.delete',          'group_name' => 'notifications'],

            // Analytics — completely missing
            ['name' => 'marketing_dashboard.view',      'group_name' => 'analytics'],
            ['name' => 'marketing_dashboard.export',    'group_name' => 'analytics'],
            ['name' => 'sales_dashboard.view',          'group_name' => 'analytics'],
            ['name' => 'sales_dashboard.export',        'group_name' => 'analytics'],
            ['name' => 'report_export.view',            'group_name' => 'analytics'],
            ['name' => 'report_export.execute',         'group_name' => 'analytics'],

            // Activity Logs — completely missing
            ['name' => 'activity_logs.view',            'group_name' => 'activity_logs'],
            ['name' => 'activity_logs.export',          'group_name' => 'activity_logs'],
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

        // ── Assign new permissions to existing default roles ──────────────

        $rolePermissions = [
            'org_admin' => [
                'visits.update','visits.delete',
                'reports.update','reports.delete',
                'weeklyplans.update','weeklyplans.delete',
                'promotions.view','promotions.create','promotions.update','promotions.delete',
                'notifications.view','notifications.create','notifications.update','notifications.delete',
                'marketing_dashboard.view','marketing_dashboard.export',
                'sales_dashboard.view','sales_dashboard.export',
                'report_export.view','report_export.execute',
                'activity_logs.view','activity_logs.export',
            ],
            'manager' => [
                'visits.update',
                'reports.update',
                'weeklyplans.update',
                'promotions.view','promotions.create','promotions.update',
                'notifications.view','notifications.create',
                'marketing_dashboard.view','marketing_dashboard.export',
                'sales_dashboard.view','sales_dashboard.export',
                'report_export.view','report_export.execute',
                'activity_logs.view',
            ],
            'staff' => [
                'visits.update',
                'reports.update',
                'weeklyplans.update',
                'promotions.view',
                'notifications.view',
                'marketing_dashboard.view',
                'sales_dashboard.view',
                'report_export.view',
            ],
            'viewer' => [
                'promotions.view',
                'notifications.view',
                'marketing_dashboard.view',
                'sales_dashboard.view',
                'report_export.view',
                'activity_logs.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permNames) {
            $role = DB::connection('platform')
                ->table('platform_default_roles')
                ->where('name', $roleName)
                ->first();

            if (! $role) continue;

            foreach ($permNames as $permName) {
                $perm = DB::connection('platform')
                    ->table('org_permission_definitions')
                    ->where('name', $permName)
                    ->first();

                if ($perm) {
                    DB::connection('platform')
                        ->table('platform_default_role_permissions')
                        ->insertOrIgnore([
                            'default_role_id'       => $role->id,
                            'org_permission_def_id' => $perm->id,
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        $names = [
            'visits.update','visits.delete',
            'reports.update','reports.delete',
            'weeklyplans.update','weeklyplans.delete',
            'promotions.view','promotions.create','promotions.update','promotions.delete',
            'notifications.view','notifications.create','notifications.update','notifications.delete',
            'marketing_dashboard.view','marketing_dashboard.export',
            'sales_dashboard.view','sales_dashboard.export',
            'report_export.view','report_export.execute',
            'activity_logs.view','activity_logs.export',
        ];

        // Remove from role permissions first (FK constraint)
        $permIds = DB::connection('platform')
            ->table('org_permission_definitions')
            ->whereIn('name', $names)
            ->pluck('id');

        DB::connection('platform')
            ->table('platform_default_role_permissions')
            ->whereIn('org_permission_def_id', $permIds)
            ->delete();

        // Then remove the definitions
        DB::connection('platform')
            ->table('org_permission_definitions')
            ->whereIn('name', $names)
            ->delete();
    }
};