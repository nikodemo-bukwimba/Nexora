<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        // Only populated when scope_type = 'specific_branches'
        Schema::connection('platform')->create('org_scope_grant_branches', function (Blueprint $table) {
            $table->char('scope_grant_id', 26);
            $table->char('org_id', 26);

            $table->primary(['scope_grant_id', 'org_id']);

            $table->foreign('scope_grant_id')
                  ->references('id')->on('org_scope_grants')
                  ->onDelete('cascade');

            $table->foreign('org_id')
                  ->references('id')->on('organizations');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_scope_grant_branches');
    }
};
