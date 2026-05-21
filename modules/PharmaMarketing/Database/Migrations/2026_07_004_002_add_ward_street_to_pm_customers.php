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
            // Inserted after county so the column order mirrors the Tanzania
            // administrative hierarchy: country → county (region) → city (district)
            // → ward → street.
            $table->string('ward', 100)->nullable()->after('county');
            $table->string('street', 100)->nullable()->after('ward');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->table('pm_customers', function (Blueprint $table) {
            $table->dropColumn(['ward', 'street']);
        });
    }
};