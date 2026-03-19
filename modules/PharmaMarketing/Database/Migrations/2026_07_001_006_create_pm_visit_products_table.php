<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_visit_products', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('visit_id', 26);
            $table->char('product_id', 26);                     // soft FK to commerce.products
            $table->string('product_name', 255);                // snapshot at time of visit
            $table->string('action', 50)->default('promoted');  // promoted|sampled|ordered|discussed
            $table->integer('samples_given')->default(0);
            $table->text('customer_feedback')->nullable();       // customer response to product
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('visit_id');
            $table->index('product_id');

            $table->foreign('visit_id')
                  ->references('id')->on('pm_field_visits')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_visit_products');
    }
};
