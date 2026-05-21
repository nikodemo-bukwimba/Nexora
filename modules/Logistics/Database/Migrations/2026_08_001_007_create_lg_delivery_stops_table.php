<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        Schema::connection('logistics')->create('lg_delivery_stops', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('run_id', 26);
            $table->char('org_id', 26);

            // Soft FKs to source modules
            $table->char('order_id', 26)->nullable();            // → commerce.orders
            $table->string('order_number', 50)->nullable();      // snapshot

            // Delivery address
            $table->string('recipient_name', 255);
            $table->string('recipient_phone', 30)->nullable();
            $table->text('address');
            $table->string('city', 100)->nullable();
            $table->char('zone_id', 26)->nullable();              // FK → lg_delivery_zones
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Sequencing within the run
            $table->integer('stop_sequence')->default(0);         // order of delivery in this run

            // Status lifecycle
            $table->string('status', 50)->default('pending');
            // pending | en_route | arrived | delivered | failed | rescheduled | cancelled

            // Cost calculation inputs
            $table->integer('unit_count')->default(1);
            $table->decimal('weight_kg', 10, 4)->nullable();
            $table->char('rate_id', 26)->nullable();              // FK → lg_delivery_rates
            $table->decimal('delivery_cost', 15, 4)->nullable(); // computed from rate card
            $table->char('currency', 3)->default('TZS');

            // Timing
            $table->timestamp('estimated_arrival_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Failure handling
            $table->string('failure_reason', 50)->nullable();
            // not_home | wrong_address | refused | damaged | other
            $table->text('failure_notes')->nullable();
            $table->date('rescheduled_date')->nullable();
            $table->string('return_status', 50)->nullable();      // pending_return | returned
            $table->timestamp('returned_at')->nullable();

            // Driver GPS at delivery
            $table->decimal('delivery_latitude', 10, 7)->nullable();
            $table->decimal('delivery_longitude', 10, 7)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('run_id');
            $table->index('order_id');
            $table->index('org_id');
            $table->index('status');
            $table->index('stop_sequence');

            $table->foreign('run_id')
                  ->references('id')->on('lg_delivery_runs')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_delivery_stops');
    }
};
