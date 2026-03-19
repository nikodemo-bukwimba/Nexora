<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('platform_role_permissions', function (Blueprint $table) {
            $table->char('platform_role_id', 26);
            $table->char('platform_permission_id', 26);

            $table->primary(['platform_role_id', 'platform_permission_id']);

            $table->foreign('platform_role_id')
                  ->references('id')->on('platform_roles')
                  ->onDelete('cascade');

            $table->foreign('platform_permission_id')
                  ->references('id')->on('platform_permissions')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_role_permissions');
    }
};
