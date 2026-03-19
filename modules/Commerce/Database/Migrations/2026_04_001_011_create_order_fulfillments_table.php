<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('order_fulfillments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('order_id', 26);
            $table->string('status', 50)->default('pending'); // pending|processing|shipped|delivered|failed
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_number', 255)->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->decimal('weight_kg', 10, 4)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('order_id');

            $table->foreign('order_id')
                  ->references('id')->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('order_fulfillments');
    }
};
