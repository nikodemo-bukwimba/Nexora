<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('audit_logs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('module', 100);
            $table->string('action', 150);         // org.membership.created|user.suspended
            $table->char('actor_id', 26)->nullable();
            $table->string('subject_type', 100);   // Organization|OrgMembership|User
            $table->char('subject_id', 26);
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            // Immutable — no updated_at, append-only
            $table->timestamp('created_at')->useCurrent();

            $table->index('actor_id');
            $table->index(['subject_type', 'subject_id']);
            $table->index('module');
            $table->index('created_at');
            $table->index('action');

            $table->foreign('actor_id')
                  ->references('id')->on('actors')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('audit_logs');
    }
};
