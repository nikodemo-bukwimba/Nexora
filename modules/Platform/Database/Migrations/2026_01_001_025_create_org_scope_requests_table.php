<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_scope_requests', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('membership_id', 26);
            $table->string('requested_scope', 50); // tree_wide|specific_branches
            // Array of org IDs for specific_branches requests — stored as jsonb
            $table->jsonb('target_org_ids')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 50)->default('pending'); // pending|approved|denied|escalated
            $table->char('reviewed_by', 26)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');

            $table->foreign('membership_id')
                  ->references('id')->on('org_memberships');

            $table->foreign('reviewed_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_scope_requests');
    }
};
