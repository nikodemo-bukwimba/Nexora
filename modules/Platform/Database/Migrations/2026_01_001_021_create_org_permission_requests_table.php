<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_permission_requests', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('requesting_org_id', 26);
            $table->char('target_org_id', 26);       // immediate parent only
            $table->char('org_role_id', 26);
            $table->char('org_permission_def_id', 26);
            $table->text('reason')->nullable();
            $table->string('status', 50)->default('pending'); // pending|approved|denied
            $table->char('reviewed_by', 26)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('requesting_org_id');

            $table->foreign('requesting_org_id')
                  ->references('id')->on('organizations');

            $table->foreign('target_org_id')
                  ->references('id')->on('organizations');

            $table->foreign('org_role_id')
                  ->references('id')->on('org_roles');

            $table->foreign('org_permission_def_id')
                  ->references('id')->on('org_permission_definitions');

            $table->foreign('reviewed_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_permission_requests');
    }
};
