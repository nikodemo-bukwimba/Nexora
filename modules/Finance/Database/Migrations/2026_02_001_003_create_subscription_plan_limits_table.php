<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('subscription_plan_limits', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('plan_id', 26);
            $table->string('feature_key', 150);             // max_members|max_branches|max_orders_per_month
            $table->string('feature_group', 100);           // members|branches|orders|storage
            $table->jsonb('limit_value');                   // -1=unlimited | {value:100} | {enabled:true}
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
            $table->index('plan_id');

            $table->foreign('plan_id')
                  ->references('id')->on('subscription_plans')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('subscription_plan_limits');
    }
};
