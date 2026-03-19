<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class CommissionConfigSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('finance')->table('commission_configs')->insertOrIgnore([
            'id'               => (string) new Ulid(),
            'name'             => 'default',
            'rate'             => 0.050000,    // 5% flat commission
            'is_active'        => true,
            'is_default'       => true,
            'effective_from'   => now(),
            'effective_until'  => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
