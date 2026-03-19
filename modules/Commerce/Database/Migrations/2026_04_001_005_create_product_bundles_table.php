<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('product_bundles', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('bundle_product_id', 26);    // the bundle parent product
            $table->char('component_variant_id', 26); // component variant inside bundle
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->index('bundle_product_id');

            $table->foreign('bundle_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            $table->foreign('component_variant_id')
                  ->references('id')->on('product_variants')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('product_bundles');
    }
};
