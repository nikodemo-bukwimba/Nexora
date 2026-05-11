<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_product_updates', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->char('created_by', 26);
            $table->string('title', 255);
            $table->text('body');
            $table->string('update_type', 50)->default('general'); // new_product|price_change|promotion|recall|general
            $table->decimal('discount_percentage', 5, 2)->nullable(); // null = no discount; value = % OFF

            // Target segment
            $table->string('target_segment', 50)->default('all');
            $table->jsonb('target_filters')->nullable();

            // Channels
            $table->boolean('send_in_app')->default(true);
            $table->boolean('send_whatsapp')->default(true);
            $table->boolean('send_sms')->default(false);

            // Product references
            $table->jsonb('product_ids')->nullable();

            // Media
            $table->string('media_url', 1000)->nullable();
            $table->string('media_type', 50)->nullable();

            // Dispatch
            $table->string('status', 50)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamps();

            $table->index('org_id');
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_product_updates');
    }
};