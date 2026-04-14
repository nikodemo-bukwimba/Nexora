<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        Schema::connection('logistics')->create('lg_courier_shipments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->char('courier_account_id', 26);
            $table->char('order_id', 26)->nullable();            // soft FK → commerce.orders
            $table->string('order_number', 50)->nullable();
            // Courier identifiers
            $table->string('tracking_number', 255)->nullable();
            $table->string('waybill_number', 255)->nullable();
            $table->string('tracking_url', 1000)->nullable();
            // Shipment details
            $table->string('status', 50)->default('booked');
            // booked|picked_up|in_transit|out_for_delivery|delivered|failed|returned
            $table->string('courier_status', 100)->nullable();   // raw status from courier API
            $table->decimal('weight_kg', 10, 4)->nullable();
            $table->integer('unit_count')->default(1);
            $table->decimal('declared_value', 15, 4)->nullable();
            $table->decimal('shipping_cost', 15, 4)->nullable();
            $table->char('currency', 3)->default('KES');
            // Recipient
            $table->string('recipient_name', 255)->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->text('delivery_address')->nullable();
            // Events
            $table->timestamp('booked_at')->useCurrent();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->jsonb('courier_events')->nullable();         // raw event log from courier
            $table->timestamps();

            $table->index('org_id');
            $table->index('order_id');
            $table->index('tracking_number');
            $table->index('status');

            $table->foreign('courier_account_id')
                  ->references('id')->on('lg_courier_accounts')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_courier_shipments');
    }
};
