<?php

namespace Modules\PharmaMarketing\Database\Seeders;

use Illuminate\Database\Seeder;

class PharmaMarketingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('PharmaMarketing module seeded. No default data required.');
    }
}
