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

            /*
            |--------------------------------------------------------------------------
            | Ownership & References
            |--------------------------------------------------------------------------
            */

            $table->char('warehouse_id', 26);

            // Soft FK — product lives in commerce schema
            $table->char('product_id', 26)
                ->index();

            // Soft FK — product variant lives in commerce schema
            $table->char('variant_id', 26)
                ->nullable()
                ->index();

            $table->char('org_id', 26)
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Batch Identification
            |--------------------------------------------------------------------------
            */

            // Human-readable reference
            $table->string('batch_number', 100)
                ->nullable()
                ->index();

            $table->string('sku', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Quantities
            |--------------------------------------------------------------------------
            */

            $table->integer('quantity_received')
                ->default(0);

            // Current stock
            $table->integer('quantity_available')
                ->default(0);

            // Reserved for pending orders
            $table->integer('quantity_reserved')
                ->default(0);

            $table->integer('quantity_damaged')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Financial
            |--------------------------------------------------------------------------
            */

            $table->decimal('unit_cost', 15, 4)
                ->nullable();

            $table->char('currency', 3)
                ->default('USD');

            /*
            |--------------------------------------------------------------------------
            | Status & Lifecycle
            |--------------------------------------------------------------------------
            */

            // active | depleted | quarantined | recalled
            $table->string('status', 50)
                ->default('active')
                ->index();

            $table->timestamp('received_at')
                ->useCurrent();

            // null = no expiry
            $table->timestamp('expires_at')
                ->nullable()
                ->index();

            $table->timestamp('best_before_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Extensible Metadata
            |--------------------------------------------------------------------------
            */

            // Extensible for vertical modules
            $table->jsonb('metadata')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('inventory')
            ->dropIfExists('inventory_batches');
    }
};