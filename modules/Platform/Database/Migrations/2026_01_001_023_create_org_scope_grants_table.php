<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_scope_grants', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('membership_id', 26);
            $table->string('scope_type', 50);    // tree_wide|specific_branches
            $table->char('granted_by', 26);
            $table->timestamp('granted_at')->useCurrent();
            $table->string('status', 50)->default('active');
            $table->timestamp('expires_at')->nullable(); // null = indefinite
            $table->timestamps();

            $table->foreign('membership_id')
                  ->references('id')->on('org_memberships')
                  ->onDelete('cascade');

            $table->foreign('granted_by')
                  ->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_scope_grants');
    }
};
