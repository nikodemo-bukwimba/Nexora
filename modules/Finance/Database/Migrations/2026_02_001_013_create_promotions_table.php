<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('promotions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // Soft FK — org lives in platform schema (null = platform-wide promotion)
            $table->char('org_id', 26)->nullable();
            $table->string('code', 50)->unique();           // SAVE10|WELCOME2026
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('type', 50);                     // percentage|fixed_amount|free_shipping
            $table->decimal('value', 15, 4);                // 10 = 10% or $10 depending on type
            $table->char('currency', 3)->nullable();        // only for fixed_amount type
            $table->decimal('min_order_amount', 15, 4)->nullable(); // minimum to qualify
            $table->decimal('max_discount_amount', 15, 4)->nullable(); // cap for percentage discounts
            $table->integer('usage_limit')->nullable();     // null = unlimited
            $table->integer('usage_count')->default(0);
            $table->integer('usage_limit_per_actor')->nullable(); // per-actor usage cap
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('promotions');
    }
};
