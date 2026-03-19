<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'inventory';

    public function up(): void
    {
        Schema::connection('inventory')->create('inventory_batches', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('warehouse_id', 26);
            // Soft FK — product lives in commerce schema
            $table->char('product_id', 26);
            $table->char('org_id', 26);
            $table->string('batch_number', 100)->nullable();   // human-readable reference
            $table->string('sku', 100)->nullable();
            $table->integer('quantity_received')->default(0);
            $table->integer('quantity_available')->default(0); // current stock
            $table->integer('quantity_reserved')->default(0);  // reserved for pending orders
            $table->integer('quantity_damaged')->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->string('status', 50)->default('active');  // active|depleted|quarantined|recalled
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();       // null = no expiry
            $table->timestamp('best_before_at')->nullable();
            $table->jsonb('metadata')->nullable();             // extensible for vertical modules
            $table->timestamps();

            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('org_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('batch_number');

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('inventory')->dropIfExists('inventory_batches');
    }
};
