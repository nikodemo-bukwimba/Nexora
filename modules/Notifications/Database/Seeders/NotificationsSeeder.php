<?php

namespace Modules\Notifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        // Seed platform-level workflow for org approval notification
        DB::connection('notifications')->table('workflow_definitions')->insertOrIgnore([
            'id'             => (string) new Ulid(),
            'org_id'         => null,
            'name'           => 'Notify on Org Approved',
            'description'    => 'Sends a push notification to org owner when their org is approved.',
            'trigger_event'  => 'platform.org.approved',
            'module'         => 'platform',
            'steps'          => json_encode([
                [
                    'type'              => 'notify',
                    'name'              => 'Notify org owner',
                    'actor_field'       => 'approved_by',
                    'notification_type' => 'org.approved',
                    'title'             => 'Organization Approved',
                    'body'              => 'Your organization has been approved and is now active.',
                ],
            ]),
            'is_active'      => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->command->info('Notifications module seeded — 1 platform workflow created.');
    }
}
