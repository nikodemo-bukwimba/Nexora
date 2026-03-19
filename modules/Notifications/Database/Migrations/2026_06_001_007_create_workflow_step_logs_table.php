<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'notifications';

    public function up(): void
    {
        Schema::connection('notifications')->create('workflow_step_logs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('run_id', 26);
            $table->integer('step_index');
            $table->string('step_type', 100);               // notify|wait|update_status|webhook|condition
            $table->string('step_name', 255)->nullable();
            $table->string('status', 50)->default('pending'); // pending|running|completed|failed|skipped
            $table->jsonb('input')->nullable();
            $table->jsonb('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('run_id');

            $table->foreign('run_id')
                  ->references('id')->on('workflow_runs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('notifications')->dropIfExists('workflow_step_logs');
    }
};
