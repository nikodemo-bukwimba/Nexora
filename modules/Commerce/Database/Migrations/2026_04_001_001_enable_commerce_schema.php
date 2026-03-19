<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        DB::connection('commerce')->statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
    }

    public function down(): void {}
};
