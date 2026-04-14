<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        Schema::connection('logistics')->create('lg_delivery_runs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('run_number', 50)->unique();           // RUN-2026-000001
            $table->char('org_id', 26);
            $table->char('driver_id', 26)->nullable();            // FK → lg_drivers
            $table->char('vehicle_id', 26)->nullable();           // FK → lg_vehicles
            $table->char('dispatched_by', 26)->nullable();        // actor_id of dispatcher
            $table->string('status', 50)->default('draft');
            // draft | dispatched | in_progress | completed | partially_completed | cancelled
            $table->date('scheduled_date');
            $table->time('scheduled_start_time')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_stops')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('scheduled_date');

            $table->foreign('driver_id')
                  ->references('id')->on('lg_drivers')
                  ->onDelete('set null');

            $table->foreign('vehicle_id')
                  ->references('id')->on('lg_vehicles')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_delivery_runs');
    }
};
