<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('invoices', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('invoice_number', 50)->unique(); // INV-2026-000001
            // Soft FKs — actors live in platform schema
            $table->char('issuer_actor_id', 26);            // who issued (platform or org actor)
            $table->char('recipient_actor_id', 26);         // who receives the invoice
            $table->char('org_id', 26)->nullable();         // org context if applicable
            // Source that triggered this invoice
            $table->string('source_type', 100)->nullable(); // OrgSubscription|Order|Manual
            $table->char('source_id', 26)->nullable();
            $table->string('status', 50)->default('draft'); // draft|sent|paid|overdue|cancelled|void
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('issuer_actor_id');
            $table->index('recipient_actor_id');
            $table->index('status');
            $table->index(['source_type', 'source_id']);
            $table->index('org_id');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('invoices');
    }
};
