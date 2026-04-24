<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->table('pm_customers', function (Blueprint $table) {
            $table->char('platform_user_id', 26)->nullable()->after('assigned_officer_id');
            $table->string('registration_source', 30)->default('admin')->after('platform_user_id');
            $table->index('platform_user_id');
            $table->index('registration_source');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->table('pm_customers', function (Blueprint $table) {
            $table->dropIndex(['platform_user_id']);
            $table->dropIndex(['registration_source']);
            $table->dropColumn(['platform_user_id', 'registration_source']);
        });
    }
};