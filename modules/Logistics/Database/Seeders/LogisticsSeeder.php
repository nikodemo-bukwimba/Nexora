<?php

namespace Modules\Logistics\Database\Seeders;

use Illuminate\Database\Seeder;

class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Logistics module seeded. Zones and rates are org-specific.');
    }
}
