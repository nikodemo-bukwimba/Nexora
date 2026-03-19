<?php

namespace Modules\Commerce\Database\Seeders;

use Illuminate\Database\Seeder;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Commerce module seeded. No default data required.');
    }
}
