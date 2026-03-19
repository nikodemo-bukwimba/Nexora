<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        // Schema already created via psql before migrations run.
        // This migration enables the uuid-ossp extension if needed.
        DB::connection('finance')->statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
    }

    public function down(): void {}
};
