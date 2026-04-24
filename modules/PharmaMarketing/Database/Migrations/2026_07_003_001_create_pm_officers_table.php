<?php
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
            // 'admin' | 'self_registered'
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 50)->default('active');
            // active | suspended | deactivated
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['org_id', 'platform_user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_officers');
    }
};