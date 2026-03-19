<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('event_registry', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 200)->unique(); // platform.actor.created|commerce.order.completed
            $table->string('module', 100);
            $table->text('description')->nullable();
            $table->jsonb('payload_schema')->nullable(); // JSON schema of expected payload
            $table->string('dispatch_mode', 20)->default('async'); // sync|async|both
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('event_registry');
    }
};
