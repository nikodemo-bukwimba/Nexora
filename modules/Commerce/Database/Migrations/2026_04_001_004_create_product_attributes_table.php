<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('product_attributes', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('variant_id', 26);
            $table->string('key', 100);    // size|color|weight|concentration
            $table->string('value', 255);  // L|Red|500g|250mg
            $table->timestamps();

            $table->unique(['variant_id', 'key']);
            $table->index('variant_id');

            $table->foreign('variant_id')
                  ->references('id')->on('product_variants')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('product_attributes');
    }
};
