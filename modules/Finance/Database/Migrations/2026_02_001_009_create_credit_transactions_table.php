<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        // Ledger model — append-only, balance computed as SUM(amount)
        // Positive = credit (money in), Negative = debit (money out)
        Schema::connection('finance')->create('credit_transactions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('account_id', 26);
            $table->decimal('amount', 15, 4);               // positive=credit, negative=debit
            $table->char('currency', 3)->default('USD');
            $table->string('type', 50);                     // topup|earned|spent|refunded|adjusted|expired
            $table->string('description', 500)->nullable();
            // Source of this transaction
            $table->string('ref_type', 100)->nullable();    // Payment|Order|Commission|Promotion
            $table->char('ref_id', 26)->nullable();
            // Immutable — no updated_at, append-only ledger
            $table->timestamp('created_at')->useCurrent();

            $table->index('account_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['ref_type', 'ref_id']);

            $table->foreign('account_id')
                  ->references('id')->on('credit_accounts')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('credit_transactions');
    }
};
