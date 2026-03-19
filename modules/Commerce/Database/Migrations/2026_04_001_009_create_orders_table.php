<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('orders', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('order_number', 50)->unique(); // ORD-2026-000001
            $table->char('basket_id', 26)->nullable();    // source basket
            $table->char('buyer_actor_id', 26);
            $table->char('seller_actor_id', 26);
            $table->char('buyer_org_id', 26)->nullable();
            $table->char('seller_org_id', 26);
            // Finance soft FKs
            $table->char('invoice_id', 26)->nullable();
            $table->char('payment_id', 26)->nullable();
            $table->string('status', 50)->default('pending'); // pending|confirmed|processing|shipped|delivered|cancelled|refunded|disputed
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('shipping_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->char('shipping_rate_id', 26)->nullable();
            $table->jsonb('shipping_address')->nullable();
            $table->jsonb('billing_address')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('buyer_actor_id');
            $table->index('seller_actor_id');
            $table->index('seller_org_id');
            $table->index('status');
            $table->index('basket_id');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('orders');
    }
};
