<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'notifications';

    public function up(): void
    {
        Schema::connection('notifications')->create('workflow_runs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('workflow_definition_id', 26);
            $table->string('trigger_event', 150);
            $table->jsonb('trigger_payload');               // event payload that triggered the run
            $table->string('status', 50)->default('running'); // running|completed|failed|cancelled
            $table->integer('current_step')->default(0);
            $table->jsonb('context')->nullable();            // shared state across steps
            $table->text('failure_reason')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('workflow_definition_id');
            $table->index('status');
            $table->index('trigger_event');

            $table->foreign('workflow_definition_id')
                  ->references('id')->on('workflow_definitions')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('notifications')->dropIfExists('workflow_runs');
    }
};
