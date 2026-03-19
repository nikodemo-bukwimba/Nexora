<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('product_variants', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('product_id', 26);
            $table->string('sku', 150)->nullable();
            $table->string('name', 255)->nullable();           // e.g. "Large / Red"
            $table->decimal('base_price', 15, 4);             // standard list price
            $table->char('currency', 3)->default('USD');
            $table->decimal('weight_kg', 10, 4)->nullable();   // for shipping calculation
            $table->decimal('cost_price', 15, 4)->nullable();  // for margin calculation
            $table->boolean('is_default')->default(false);     // default variant shown to buyer
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->index('sku');

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('product_variants');
    }
};
