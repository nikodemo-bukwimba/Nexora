<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('order_items', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('order_id', 26);
            $table->char('variant_id', 26);
            $table->char('product_id', 26);              // denormalized
            $table->string('product_name', 255);         // snapshot at order time
            $table->string('variant_name', 255)->nullable();
            $table->string('sku', 150)->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 4);
            $table->decimal('subtotal', 15, 4);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4);
            $table->char('currency', 3)->default('USD');
            // Inventory soft FK
            $table->char('reservation_id', 26)->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('variant_id');

            $table->foreign('order_id')
                  ->references('id')->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('order_items');
    }
};
