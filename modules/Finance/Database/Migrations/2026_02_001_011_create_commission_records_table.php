<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('commission_records', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('commission_config_id', 26);
            $table->char('payment_id', 26);
            // Soft FK — actor lives in platform schema
            $table->char('actor_id', 26);                   // who paid the commission
            $table->decimal('transaction_amount', 15, 4);   // original transaction amount
            $table->decimal('commission_rate', 8, 6);       // rate applied at time of transaction
            $table->decimal('commission_amount', 15, 4);    // actual commission charged
            $table->char('currency', 3)->default('USD');
            $table->string('status', 50)->default('pending'); // pending|collected|waived|refunded
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();

            $table->index('payment_id');
            $table->index('actor_id');
            $table->index('status');

            $table->foreign('commission_config_id')
                  ->references('id')->on('commission_configs');

            $table->foreign('payment_id')
                  ->references('id')->on('payments');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('commission_records');
    }
};
