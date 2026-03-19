<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'notifications';

    public function up(): void
    {
        Schema::connection('notifications')->create('workflow_definitions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26)->nullable();       // null = platform-level workflow
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('trigger_event', 150);          // platform event name e.g. platform.org.approved
            $table->string('module', 100);                 // platform|finance|commerce|inventory
            $table->jsonb('steps');                        // ordered array of step definitions
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('trigger_event');
            $table->index('org_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('notifications')->dropIfExists('workflow_definitions');
    }
};
