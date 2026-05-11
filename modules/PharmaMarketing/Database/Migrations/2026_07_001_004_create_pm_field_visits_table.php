<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_field_visits', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->char('customer_id', 26);
            $table->char('officer_actor_id', 26);
            $table->char('weekly_plan_item_id', 26)->nullable();

            // Status
            $table->string('status', 50)->default('in_progress');
            $table->string('admin_status', 50)->nullable();       // pending|accepted|flagged
            $table->string('visit_type', 50)->default('routine');

            // Visit timing
            $table->timestamp('check_in_at')->useCurrent();
            $table->timestamp('check_out_at')->nullable();
            $table->integer('duration_minutes')->nullable();

            // GPS at check-in
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->integer('check_in_gps_accuracy_meters')->nullable();

            // GPS at check-out
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();

            // Visit content
            $table->text('objective')->nullable();
            $table->text('discussion_summary')->nullable();
            $table->text('outcome')->nullable();
            $table->string('outcome_status', 50)->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->date('follow_up_date')->nullable();

            // Contact person
            $table->char('contact_person_id', 26)->nullable();
            $table->string('contact_person_name', 255)->nullable();

            // Admin review
            $table->text('flag_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->char('reviewed_by', 26)->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('customer_id');
            $table->index('officer_actor_id');
            $table->index('status');
            $table->index('admin_status');
            $table->index('check_in_at');
            $table->index('weekly_plan_item_id');

            $table->foreign('customer_id')
                  ->references('id')->on('pm_customers')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_field_visits');
    }
};