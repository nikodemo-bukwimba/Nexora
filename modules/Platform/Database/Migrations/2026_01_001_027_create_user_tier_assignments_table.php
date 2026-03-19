<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('user_tier_assignments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26);
            $table->char('tier_id', 26);
            $table->char('assigned_by', 26)->nullable(); // null = system (on register)
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();  // null = indefinite
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('tier_id')
                  ->references('id')->on('platform_tiers');

            $table->foreign('assigned_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('user_tier_assignments');
    }
};
