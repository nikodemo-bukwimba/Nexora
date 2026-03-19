<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('shipping_rates', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->string('name', 100);
            $table->string('method', 50)->default('standard');  // standard|express|overnight|pickup
            $table->string('calculation_type', 50);              // flat|weight_based|value_based
            $table->decimal('base_rate', 15, 4)->default(0);
            $table->decimal('rate_per_kg', 10, 4)->nullable();
            $table->decimal('rate_per_value_percent', 8, 4)->nullable();
            $table->decimal('free_shipping_threshold', 15, 4)->nullable(); // order value above this = free
            $table->decimal('min_weight_kg', 10, 4)->nullable();
            $table->decimal('max_weight_kg', 10, 4)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('org_id');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('shipping_rates');
    }
};
