<?php
// FILE: modules/PharmaMarketing/Database/Migrations/2026_07_003_001_create_pm_officers_table.php
// CHANGE: Three new nullable columns added for transfer audit trail.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_officers', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26)->index();
            $table->char('branch_id', 26)->index();
            $table->char('platform_user_id', 26)->nullable()->index();
            $table->char('actor_id', 26)->nullable()->index();
            $table->string('registration_source', 30)->default('admin');
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 50)->default('active'); // active|suspended|deactivated
            $table->jsonb('metadata')->nullable();

            // ── NEW: transfer audit columns ───────────────────────────────
            // All nullable — only populated after a transfer occurs.
            $table->char('previous_branch_id', 26)->nullable()->index();
            $table->timestamp('transferred_at')->nullable();
            $table->char('transferred_by', 26)->nullable()->index();
            // ─────────────────────────────────────────────────────────────

            $table->timestamps();
            $table->softDeletes();

            // KEEP: one active officer record per (org, platform user).
            $table->unique(['org_id', 'platform_user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_officers');
    }
};
