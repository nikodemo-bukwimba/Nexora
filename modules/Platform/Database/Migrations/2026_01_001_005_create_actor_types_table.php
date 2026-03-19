<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('actor_types', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 100)->unique(); // user|organization|ai_agent|iot_device|external_system|virtual_entity
            $table->string('source', 20)->default('platform'); // platform|module
            $table->string('module', 100)->nullable(); // which module registered it (null = platform)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('actor_types');
    }
};
