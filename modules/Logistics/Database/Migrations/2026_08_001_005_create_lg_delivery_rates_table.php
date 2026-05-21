<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        // Rate cards: cost per delivery = base + (unit_count × per_unit) + (weight_kg × per_kg)
        Schema::connection('logistics')->create('lg_delivery_rates', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->char('zone_id', 26)->nullable();             // null = default rate (all zones)
            $table->string('name', 100);
            $table->decimal('base_rate', 15, 4)->default(0);     // fixed base per delivery
            $table->decimal('rate_per_unit', 15, 4)->default(0); // × unit_count
            $table->decimal('rate_per_kg', 15, 4)->default(0);   // × weight_kg
            $table->decimal('min_charge', 15, 4)->default(0);    // minimum total charge
            $table->decimal('max_charge', 15, 4)->nullable();    // cap (null = no cap)
            $table->char('currency', 3)->default('TZS');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('org_id');
            $table->index('zone_id');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_delivery_rates');
    }
};
