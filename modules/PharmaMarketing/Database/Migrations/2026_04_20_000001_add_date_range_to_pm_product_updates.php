// Modules/PharmaMarketing/Database/Migrations/2026_04_20_000001_add_date_range_to_pm_product_updates.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->table('pm_product_updates', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('scheduled_at');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->table('pm_product_updates', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};