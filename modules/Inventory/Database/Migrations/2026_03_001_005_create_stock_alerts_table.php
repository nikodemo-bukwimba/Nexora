<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'inventory';

    public function up(): void
    {
        Schema::connection('inventory')->create('stock_alerts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('warehouse_id', 26)->nullable(); // null = org-wide alert
            $table->char('product_id', 26)->nullable();   // null = any product
            $table->char('batch_id', 26)->nullable();     // null = product-level alert
            $table->char('org_id', 26);
            $table->string('type', 50);                   // low_stock|out_of_stock|near_expiry|expired|damaged
            $table->string('status', 50)->default('active'); // active|acknowledged|resolved
            $table->integer('threshold')->nullable();     // for low_stock alerts
            $table->integer('current_value')->nullable(); // current qty or days until expiry
            $table->text('message')->nullable();
            $table->char('acknowledged_by', 26)->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('product_id');
            $table->index(['type', 'status']);

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('set null');

            $table->foreign('batch_id')
                  ->references('id')->on('inventory_batches')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('inventory')->dropIfExists('stock_alerts');
    }
};
