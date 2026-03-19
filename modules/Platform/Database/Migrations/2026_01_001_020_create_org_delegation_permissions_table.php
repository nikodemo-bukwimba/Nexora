<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_delegation_permissions', function (Blueprint $table) {
            $table->char('delegation_id', 26);
            $table->char('org_permission_def_id', 26);

            $table->primary(['delegation_id', 'org_permission_def_id']);

            $table->foreign('delegation_id')
                  ->references('id')->on('org_role_delegations')
                  ->onDelete('cascade');

            $table->foreign('org_permission_def_id')
                  ->references('id')->on('org_permission_definitions');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_delegation_permissions');
    }
};
