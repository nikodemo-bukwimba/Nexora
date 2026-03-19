<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            ['key' => 'platform.registration_open', 'value' => true,  'description' => 'Allow new user registrations',     'module' => 'identity'],
            ['key' => 'platform.org_self_signup',   'value' => true,  'description' => 'Allow orgs to self-register',      'module' => 'organizations'],
            ['key' => 'marketplace.enabled',        'value' => false, 'description' => 'Enable marketplace module',        'module' => 'marketplace'],
            ['key' => 'communications.enabled',     'value' => false, 'description' => 'Enable communications module',     'module' => 'communications'],
            ['key' => 'social.enabled',             'value' => false, 'description' => 'Enable social/community module',   'module' => 'social'],
        ];

        foreach ($flags as $flag) {
            DB::connection('platform')->table('platform_feature_flags')->insertOrIgnore([
                'id'          => (string) new Ulid(),
                'key'         => $flag['key'],
                'value'       => $flag['value'],
                'description' => $flag['description'],
                'module'      => $flag['module'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
