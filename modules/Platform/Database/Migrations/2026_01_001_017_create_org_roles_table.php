<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_roles', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('root_org_id', 26);
            $table->string('name', 100);
            $table->string('slug', 100)->nullable();
            $table->string('source', 20)->default('custom');
            $table->char('default_role_id', 26)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['root_org_id', 'name']);
            $table->index('slug');

            $table->foreign('root_org_id')
                  ->references('id')->on('organizations');

            $table->foreign('default_role_id')
                  ->references('id')->on('platform_default_roles')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_roles');
    }
};