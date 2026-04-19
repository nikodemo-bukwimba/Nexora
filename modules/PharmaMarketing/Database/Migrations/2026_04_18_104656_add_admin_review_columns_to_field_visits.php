<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->table('pm_field_visits', function (Blueprint $table) {
            $table->string('admin_status', 50)->nullable()->after('status');
            $table->text('flag_reason')->nullable()->after('admin_status');
            $table->text('admin_notes')->nullable()->after('flag_reason');
            $table->char('reviewed_by', 26)->nullable()->after('admin_notes');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->table('pm_field_visits', function (Blueprint $table) {
            $table->dropColumn(['admin_status', 'flag_reason', 'admin_notes', 'reviewed_by', 'reviewed_at']);
        });
    }
};