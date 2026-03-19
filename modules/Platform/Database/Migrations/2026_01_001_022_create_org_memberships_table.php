<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_memberships', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('user_id', 26);
            $table->char('org_id', 26);
            $table->char('org_role_id', 26);
            // Level is independent of role. 0-100. Level 100 = tree-wide authority.
            $table->smallInteger('level')->default(0);
            $table->char('invited_by', 26)->nullable();
            $table->string('status', 50)->default('invited'); // invited|active|suspended
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'org_id', 'org_role_id']);
            $table->index('user_id');
            $table->index('org_id');
            $table->index('status');

            $table->foreign('user_id')
                  ->references('id')->on('users');

            $table->foreign('org_id')
                  ->references('id')->on('organizations');

            $table->foreign('org_role_id')
                  ->references('id')->on('org_roles');

            $table->foreign('invited_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });

        // Enforce level range at database level
        \Illuminate\Support\Facades\DB::connection('platform')->statement(
            'ALTER TABLE org_memberships ADD CONSTRAINT chk_level_range CHECK (level BETWEEN 0 AND 100)'
        );
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_memberships');
    }
};
