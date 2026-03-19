<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    /**
     * Bootstrap the first platform super admin.
     *
     * Configure via .env:
     *   SUPER_ADMIN_USERNAME=testuser2
     *
     * Usage:
     *   php artisan db:seed --class="Modules\Platform\Database\Seeders\SuperAdminSeeder"
     *
     * This seeder is safe to run multiple times — uses insertOrIgnore.
     * Run this once after initial deployment to bootstrap platform access.
     * After this, use the API to manage staff roles.
     */
    public function run(): void
    {
        $username = env('SUPER_ADMIN_USERNAME');

        if (! $username) {
            $this->command->warn('  Skipped: SUPER_ADMIN_USERNAME not set in .env');
            return;
        }

        $user = DB::connection('platform')
            ->table('users')
            ->where('username', $username)
            ->first();

        if (! $user) {
            $this->command->error("  User '{$username}' not found. Register first via the API.");
            return;
        }

        $role = DB::connection('platform')
            ->table('platform_roles')
            ->where('name', 'super_admin')
            ->first();

        if (! $role) {
            $this->command->error('  super_admin role not found. Run db:seed first.');
            return;
        }

        $exists = DB::connection('platform')
            ->table('user_platform_roles')
            ->where('user_id', $user->id)
            ->where('platform_role_id', $role->id)
            ->exists();

        if ($exists) {
            $this->command->info("  '{$username}' already has super_admin role. Skipped.");
            return;
        }

        DB::connection('platform')->table('user_platform_roles')->insert([
            'user_id'          => $user->id,
            'platform_role_id' => $role->id,
            'granted_by'       => $user->id, // self-bootstrap
            'granted_at'       => now(),
        ]);

        $this->command->info("  super_admin assigned to '{$username}' successfully.");
    }
}
