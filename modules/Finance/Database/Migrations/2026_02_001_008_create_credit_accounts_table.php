<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('credit_accounts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // Soft FK — actor lives in platform schema
            $table->char('actor_id', 26)->unique();         // one account per actor, global
            $table->char('currency', 3)->default('USD');
            $table->string('status', 50)->default('active'); // active|frozen|closed
            $table->timestamps();

            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('credit_accounts');
    }
};
