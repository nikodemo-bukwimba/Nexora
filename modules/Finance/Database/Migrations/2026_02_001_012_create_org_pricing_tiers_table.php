<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('org_pricing_tiers', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // Soft FK — org lives in platform schema
            $table->char('org_id', 26);
            $table->string('name', 100);                    // Wholesale|Retail|VIP|Staff
            $table->text('description')->nullable();
            $table->decimal('discount_percent', 8, 4)->default(0); // % off standard price
            $table->boolean('is_default')->default(false);  // default tier for this org
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['org_id', 'name']);
            $table->index('org_id');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('org_pricing_tiers');
    }
};
