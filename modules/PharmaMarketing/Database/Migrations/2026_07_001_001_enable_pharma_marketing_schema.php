<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        DB::connection('pharma_marketing')->statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
        DB::connection('pharma_marketing')->statement('CREATE EXTENSION IF NOT EXISTS "postgis"');
    }

    public function down(): void {}
};
