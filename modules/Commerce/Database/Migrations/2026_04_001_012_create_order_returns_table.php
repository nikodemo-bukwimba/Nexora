<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('order_returns', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('order_id', 26);
            $table->char('requested_by', 26);            // buyer actor_id
            $table->string('reason', 500);
            $table->string('status', 50)->default('pending'); // pending|approved|rejected|completed
            $table->string('resolution', 50)->nullable();      // refund|replacement|store_credit
            $table->decimal('refund_amount', 15, 4)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->char('reviewed_by', 26)->nullable();   // seller actor_id
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');

            $table->foreign('order_id')
                  ->references('id')->on('orders')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('order_returns');
    }
};
