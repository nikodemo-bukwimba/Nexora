<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('org_subscriptions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // Soft FK — org lives in platform schema, no DB-level FK
            $table->char('org_id', 26)->unique();
            $table->char('plan_id', 26);
            $table->string('status', 50)->default('active'); // active|trial|cancelled|expired|past_due
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            // External gateway reference (Stripe subscription ID etc)
            $table->string('gateway_subscription_id', 255)->nullable();
            $table->string('gateway', 50)->nullable();       // stripe|manual|etc
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('status');

            $table->foreign('plan_id')
                  ->references('id')->on('subscription_plans');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('org_subscriptions');
    }
};
