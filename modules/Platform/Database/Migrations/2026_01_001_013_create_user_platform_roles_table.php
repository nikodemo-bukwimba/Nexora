<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('user_platform_roles', function (Blueprint $table) {
            $table->char('user_id', 26);
            $table->char('platform_role_id', 26);
            $table->char('granted_by', 26)->nullable();
            $table->timestamp('granted_at')->useCurrent();

            $table->primary(['user_id', 'platform_role_id']);

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('platform_role_id')
                  ->references('id')->on('platform_roles')
                  ->onDelete('cascade');

            $table->foreign('granted_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('user_platform_roles');
    }
};
