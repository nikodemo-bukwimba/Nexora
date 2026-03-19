<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('platform_roles', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 100)->unique(); // super_admin|support_agent|billing_admin|etc
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(true); // system roles cannot be deleted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_roles');
    }
};
