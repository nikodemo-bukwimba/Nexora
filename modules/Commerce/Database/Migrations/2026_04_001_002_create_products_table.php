<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        Schema::connection('commerce')->create('products', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // Soft FK — org lives in platform schema
            $table->char('org_id', 26);
            $table->char('seller_actor_id', 26);
            $table->string('name', 255);
            $table->string('slug', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('type', 50)->default('physical'); // physical|service|digital|bundle
            $table->string('status', 50)->default('draft');   // draft|active|archived|suspended
            $table->boolean('requires_confirmation')->default(false); // false = auto-confirm on payment
            $table->boolean('track_inventory')->default(true);        // false for services/digital
            $table->jsonb('media')->nullable();                // image URLs
            $table->jsonb('attributes')->nullable();           // searchable product-level attributes
            $table->jsonb('metadata')->nullable();             // vertical extensibility
            $table->timestamps();
            $table->softDeletes();

            $table->index('org_id');
            $table->index('seller_actor_id');
            $table->index('type');
            $table->index('status');
            $table->unique(['org_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('products');
    }
};
