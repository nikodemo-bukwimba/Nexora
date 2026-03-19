<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'notifications';

    public function up(): void
    {
        DB::connection('notifications')->statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
    }

    public function down(): void {}
};
