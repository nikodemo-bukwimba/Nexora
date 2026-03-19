<?php

namespace Modules\Communications\Database\Seeders;

use Illuminate\Database\Seeder;

class CommunicationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Communications module seeded. No default data required.');
    }
}
