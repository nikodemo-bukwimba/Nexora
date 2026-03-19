<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'inventory';

    public function up(): void
    {
        // Immutable movement ledger — every quantity change is recorded here
        Schema::connection('inventory')->create('stock_movements', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('batch_id', 26);
            $table->char('warehouse_id', 26);
            $table->char('product_id', 26);
            $table->char('org_id', 26);
            $table->string('type', 50);                   // received|sold|returned|adjusted|reserved|released|damaged|transferred
            $table->integer('quantity');                   // positive=in, negative=out
            $table->integer('quantity_before');           // snapshot before movement
            $table->integer('quantity_after');            // snapshot after movement
            // Source that triggered this movement
            $table->string('ref_type', 100)->nullable();  // Order|Return|Adjustment|Transfer
            $table->char('ref_id', 26)->nullable();
            // Soft FK — actor who performed this
            $table->char('performed_by', 26)->nullable();
            $table->text('notes')->nullable();
            // Immutable — no updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index('batch_id');
            $table->index('product_id');
            $table->index('org_id');
            $table->index('type');
            $table->index(['ref_type', 'ref_id']);
            $table->index('created_at');

            $table->foreign('batch_id')
                  ->references('id')->on('inventory_batches')
                  ->onDelete('restrict');

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses');
        });
    }

    public function down(): void
    {
        Schema::connection('inventory')->dropIfExists('stock_movements');
    }
};
