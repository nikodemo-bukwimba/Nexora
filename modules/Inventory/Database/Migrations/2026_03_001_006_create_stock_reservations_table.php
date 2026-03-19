<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'inventory';

    public function up(): void
    {
        // Tracks stock reserved for pending orders
        // Released when order is fulfilled or cancelled
        Schema::connection('inventory')->create('stock_reservations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('batch_id', 26);
            $table->char('product_id', 26);
            $table->char('org_id', 26);
            $table->integer('quantity');
            $table->string('ref_type', 100)->nullable();  // Order
            $table->char('ref_id', 26)->nullable();
            $table->string('status', 50)->default('active'); // active|released|fulfilled
            $table->timestamp('expires_at')->nullable();   // auto-release if order not confirmed
            $table->timestamps();

            $table->index('batch_id');
            $table->index('product_id');
            $table->index(['ref_type', 'ref_id']);
            $table->index('status');

            $table->foreign('batch_id')
                  ->references('id')->on('inventory_batches')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('inventory')->dropIfExists('stock_reservations');
    }
};
