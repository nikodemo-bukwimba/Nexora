<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_customer_contacts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('customer_id', 26);
            $table->string('name', 255);
            $table->string('role', 100)->nullable();        // owner|procurement|pharmacist|doctor|etc
            $table->string('phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_id');

            $table->foreign('customer_id')
                  ->references('id')->on('pm_customers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_customer_contacts');
    }
};
