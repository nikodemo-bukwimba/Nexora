<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_promotion_product_overrides', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // FK to pm_product_updates (the promotion)
            $table->char('product_update_id', 26)->index();

            // FK to commerce.product_variants — the specific variant being overridden.
            // Cross-schema FK cannot be enforced by the DB, enforced at app level.
            $table->char('variant_id', 26)->index();

            // When null → falls back to pm_product_updates.discount_percentage
            // When set  → this variant uses its own percentage instead
            $table->decimal('discount_percentage', 5, 2)->nullable();

            $table->timestamps();

            $table->unique(['product_update_id', 'variant_id']);

            $table->foreign('product_update_id')
                  ->references('id')
                  ->on('pm_product_updates')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_promotion_product_overrides');
    }
};