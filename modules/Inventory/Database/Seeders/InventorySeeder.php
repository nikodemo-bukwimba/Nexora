<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // No default data needed — warehouses and stock are org-specific
        $this->command->info('Inventory module seeded. No default data required.');
    }
}
