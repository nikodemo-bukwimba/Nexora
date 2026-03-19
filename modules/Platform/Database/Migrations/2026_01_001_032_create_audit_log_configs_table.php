<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('audit_log_configs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('module', 100);
            $table->string('action', 150);
            $table->boolean('is_enabled')->default(true);
            $table->integer('retention_days')->nullable(); // null = keep forever
            $table->timestamps();

            $table->unique(['module', 'action']);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('audit_log_configs');
    }
};
