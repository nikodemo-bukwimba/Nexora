<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('platform_tiers', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 100)->unique(); // free|premium|enterprise
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false); // auto-assigned on registration
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_tiers');
    }
};
