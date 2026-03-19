<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('payments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('invoice_id', 26)->nullable();     // null = direct payment (no invoice)
            // Soft FKs
            $table->char('payer_actor_id', 26);
            $table->char('payee_actor_id', 26);
            $table->decimal('amount', 15, 4);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 50)->default('pending'); // pending|completed|failed|refunded|partially_refunded
            $table->string('method', 50)->nullable();         // card|bank_transfer|credit|manual|etc
            $table->string('gateway', 50)->nullable();        // stripe|manual|etc
            $table->string('gateway_payment_id', 255)->nullable();
            $table->string('gateway_status', 100)->nullable();
            $table->decimal('gateway_fee', 15, 4)->default(0);
            $table->decimal('net_amount', 15, 4)->nullable(); // amount - gateway_fee
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('payer_actor_id');
            $table->index('payee_actor_id');
            $table->index('status');
            $table->index('gateway_payment_id');

            $table->foreign('invoice_id')
                  ->references('id')->on('invoices')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('payments');
    }
};
