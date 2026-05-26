<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('branch_variant_price_overrides', function (Blueprint $table) {
            $table->char('id', 26)->primary();

            // Branch that owns this override — never a root org.
            // Root org uses the variant's canonical base_price.
            $table->char('org_id', 26);

            $table->char('variant_id', 26);

            // Override price set by the branch (e.g. base_price + transport cost).
            $table->decimal('price', 15, 4);
            $table->string('currency', 3)->default('TZS');

            // Who set the override
            $table->char('created_by', 26)->nullable();

            $table->timestamps();

            // One override per variant per branch
            $table->unique(['org_id', 'variant_id']);

            $table->index('org_id');
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('branch_variant_price_overrides');
    }
};