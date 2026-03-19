<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_weekly_plans', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->char('officer_actor_id', 26);               // plan belongs to this officer
            $table->date('week_start_date');                     // always Monday
            $table->date('week_end_date');                       // always Friday/Sunday
            $table->string('status', 50)->default('draft');     // draft|submitted|approved|rejected|active|completed
            $table->text('objectives')->nullable();              // officer-written goals for the week
            $table->text('notes')->nullable();
            // Approval workflow
            $table->char('approved_by', 26)->nullable();        // head officer actor_id
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('officer_actor_id');
            $table->index('week_start_date');
            $table->index('status');
            $table->unique(['officer_actor_id', 'week_start_date']); // one plan per officer per week
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_weekly_plans');
    }
};
