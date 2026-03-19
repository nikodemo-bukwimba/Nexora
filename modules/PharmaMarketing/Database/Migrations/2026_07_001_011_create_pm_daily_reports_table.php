<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_daily_reports', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->char('officer_actor_id', 26);
            $table->date('report_date');
            $table->string('status', 50)->default('draft');   // draft|submitted|approved|rejected
            // Activity summary (auto-computed from visits)
            $table->integer('planned_visits')->default(0);
            $table->integer('completed_visits')->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('samples_distributed')->default(0);
            // Officer narrative
            $table->text('summary')->nullable();
            $table->text('challenges')->nullable();
            $table->text('achievements')->nullable();
            $table->text('next_day_plan')->nullable();
            // Approval
            $table->char('reviewed_by', 26)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('officer_actor_id');
            $table->index('report_date');
            $table->index('status');
            $table->unique(['officer_actor_id', 'report_date']); // one report per officer per day
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_daily_reports');
    }
};
