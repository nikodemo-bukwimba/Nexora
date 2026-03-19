<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('invoice_line_items', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('invoice_id', 26);
            $table->string('description', 500);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('subtotal', 15, 4);            // quantity * unit_price
            $table->decimal('tax_rate', 8, 4)->default(0); // as decimal e.g. 0.16 = 16%
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total', 15, 4);
            $table->char('currency', 3)->default('USD');
            // Reference to what this line item is for
            $table->string('ref_type', 100)->nullable();   // Product|Subscription|Service
            $table->char('ref_id', 26)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('invoice_id');

            $table->foreign('invoice_id')
                  ->references('id')->on('invoices')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('invoice_line_items');
    }
};
