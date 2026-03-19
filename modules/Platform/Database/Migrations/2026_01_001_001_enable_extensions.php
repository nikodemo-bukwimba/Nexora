<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        DB::connection('platform')->statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
        DB::connection('platform')->statement('CREATE EXTENSION IF NOT EXISTS "ltree"');
    }

    public function down(): void
    {
        // Extensions are shared across schemas — never drop in down()
    }
};
