<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_weekly_plan_items', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('plan_id', 26);
            $table->date('planned_date');
            $table->string('item_type', 50)->default('customer_visit');
            // customer_visit|office_work|training|meeting|promotion_event|other
            $table->char('customer_id', 26)->nullable();        // for customer_visit type
            $table->string('customer_name', 255)->nullable();   // snapshot
            $table->string('title', 255)->nullable();           // for non-visit items
            $table->text('objective')->nullable();
            $table->time('planned_start_time')->nullable();
            $table->time('planned_end_time')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('status', 50)->default('planned');  // planned|completed|skipped|rescheduled
            $table->char('visit_id', 26)->nullable();           // linked to actual visit when done
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('plan_id');
            $table->index('planned_date');
            $table->index('customer_id');

            $table->foreign('plan_id')
                  ->references('id')->on('pm_weekly_plans')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_weekly_plan_items');
    }
};
