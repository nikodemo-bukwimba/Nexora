<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubscriptionPlanSeeder::class,
            CommissionConfigSeeder::class,
        ]);
    }
}
