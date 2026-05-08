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
            // null  = new product campaign (no discount)
            // value = discount campaign e.g. 20.00 means 20% OFF
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('update_type');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->table('pm_product_updates', function (Blueprint $table) {
            $table->dropColumn('discount_percentage');
        });
    }
};