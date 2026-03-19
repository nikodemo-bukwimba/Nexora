<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('basket_items', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('basket_id', 26);
            $table->char('variant_id', 26);
            $table->char('seller_actor_id', 26);         // denormalized for quick split at checkout
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 4);        // price snapshotted at time of add
            $table->char('currency', 3)->default('USD');
            $table->timestamps();

            $table->unique(['basket_id', 'variant_id']);
            $table->index('basket_id');

            $table->foreign('basket_id')
                  ->references('id')->on('baskets')
                  ->onDelete('cascade');

            $table->foreign('variant_id')
                  ->references('id')->on('product_variants')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('basket_items');
    }
};
