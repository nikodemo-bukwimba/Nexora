<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('platform_default_role_permissions', function (Blueprint $table) {
            $table->char('default_role_id', 26);
            $table->char('org_permission_def_id', 26);

            $table->primary(['default_role_id', 'org_permission_def_id']);

            $table->foreign('default_role_id')
                  ->references('id')->on('platform_default_roles')
                  ->onDelete('cascade');

            $table->foreign('org_permission_def_id')
                  ->references('id')->on('org_permission_definitions')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_default_role_permissions');
    }
};
