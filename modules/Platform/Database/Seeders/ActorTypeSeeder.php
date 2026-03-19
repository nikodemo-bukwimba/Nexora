<?php

namespace Modules\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class ActorTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'user',            'description' => 'Registered human user with system credentials'],
            ['name' => 'organization',    'description' => 'A tenant organization or branch'],
            ['name' => 'ai_agent',        'description' => 'An AI agent participating in institutional processes'],
            ['name' => 'iot_device',      'description' => 'An IoT device or sensor'],
            ['name' => 'external_system', 'description' => 'Third-party or external system integration'],
            ['name' => 'virtual_entity',  'description' => 'A bot, automated process, or virtual participant'],
        ];

        foreach ($types as $type) {
            DB::connection('platform')->table('actor_types')->insertOrIgnore([
                'id'          => (string) new Ulid(),
                'name'        => $type['name'],
                'source'      => 'platform',
                'description' => $type['description'],
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
