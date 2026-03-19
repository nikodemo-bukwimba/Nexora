<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('promotion_usages', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('promotion_id', 26);
            // Soft FK — actor lives in platform schema
            $table->char('actor_id', 26);
            // Soft FK — order/invoice lives in commerce/finance schema
            $table->string('ref_type', 100)->nullable();    // Order|Invoice
            $table->char('ref_id', 26)->nullable();
            $table->decimal('discount_applied', 15, 4);
            $table->char('currency', 3)->default('USD');
            $table->timestamp('used_at')->useCurrent();

            $table->index('promotion_id');
            $table->index('actor_id');
            $table->index(['ref_type', 'ref_id']);

            $table->foreign('promotion_id')
                  ->references('id')->on('promotions')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('promotion_usages');
    }
};
