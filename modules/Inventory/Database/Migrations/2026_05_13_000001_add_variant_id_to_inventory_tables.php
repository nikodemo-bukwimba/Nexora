<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'inventory';

    public function up(): void
    {
        // ── inventory_batches ──────────────────────────────────
        Schema::connection('inventory')->table('inventory_batches', function (Blueprint $table) {
            // Nullable so existing rows are not broken
            $table->char('variant_id', 26)->nullable()->after('product_id');
            $table->index('variant_id');
        });

        // ── stock_movements ────────────────────────────────────
        // Append variant_id to the movement ledger so reports can
        // filter by variant without joining back to batches.
        Schema::connection('inventory')->table('stock_movements', function (Blueprint $table) {
            $table->char('variant_id', 26)->nullable()->after('product_id');
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::connection('inventory')->table('inventory_batches', function (Blueprint $table) {
            $table->dropIndex(['variant_id']);
            $table->dropColumn('variant_id');
        });

        Schema::connection('inventory')->table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['variant_id']);
            $table->dropColumn('variant_id');
        });
    }
};