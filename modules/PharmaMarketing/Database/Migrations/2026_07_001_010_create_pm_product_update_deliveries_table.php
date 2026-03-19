<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_product_update_deliveries', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('product_update_id', 26);
            $table->char('customer_id', 26);
            $table->string('channel', 50);                  // in_app|whatsapp|sms
            $table->string('status', 50)->default('pending'); // pending|sent|delivered|failed|read
            $table->string('recipient_address', 255)->nullable(); // phone or actor_id
            $table->string('external_message_id', 255)->nullable(); // WhatsApp/SMS gateway reference
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('product_update_id');
            $table->index('customer_id');
            $table->index(['product_update_id', 'channel']);
            $table->index('status');

            $table->foreign('product_update_id')
                  ->references('id')->on('pm_product_updates')
                  ->onDelete('cascade');
            $table->foreign('customer_id')
                  ->references('id')->on('pm_customers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_product_update_deliveries');
    }
};
