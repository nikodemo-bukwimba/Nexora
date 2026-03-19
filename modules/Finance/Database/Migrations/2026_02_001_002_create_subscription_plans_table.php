<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('subscription_plans', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 100)->unique();          // basic|professional|enterprise
            $table->text('description')->nullable();
            $table->decimal('price', 15, 4)->default(0);    // monthly price
            $table->char('currency', 3)->default('USD');
            $table->string('billing_cycle', 20)->default('monthly'); // monthly|annual
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);    // false = custom/negotiated
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('subscription_plans');
    }
};
