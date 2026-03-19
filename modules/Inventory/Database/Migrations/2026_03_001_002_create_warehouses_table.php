<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'inventory';

    public function up(): void
    {
        Schema::connection('inventory')->create('warehouses', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // Soft FK — org lives in platform schema
            $table->char('org_id', 26);
            $table->char('actor_id', 26)->nullable(); // warehouse as an actor
            $table->string('name', 255);
            $table->string('code', 50)->nullable();   // internal reference code
            $table->string('type', 50)->default('standard'); // standard|cold|bonded|virtual
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('status', 50)->default('active'); // active|inactive|full
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('org_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('inventory')->dropIfExists('warehouses');
    }
};
