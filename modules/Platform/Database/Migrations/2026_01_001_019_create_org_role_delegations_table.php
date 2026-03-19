<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_role_delegations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('parent_org_id', 26);
            $table->char('child_org_id', 26);
            $table->char('org_role_id', 26);
            $table->char('granted_by', 26);
            $table->timestamp('granted_at')->useCurrent();
            $table->string('status', 50)->default('active'); // active|revoked
            $table->timestamps();

            $table->unique(['parent_org_id', 'child_org_id', 'org_role_id']);

            $table->foreign('parent_org_id')
                  ->references('id')->on('organizations');

            $table->foreign('child_org_id')
                  ->references('id')->on('organizations');

            $table->foreign('org_role_id')
                  ->references('id')->on('org_roles');

            $table->foreign('granted_by')
                  ->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_role_delegations');
    }
};
