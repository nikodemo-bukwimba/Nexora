<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        Schema::connection('logistics')->create('lg_delivery_zones', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->string('name', 100);                         // Nairobi CBD, Westlands, Thika Road
            $table->string('code', 20)->nullable();              // Z1, Z2, Z3
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('org_id');
            $table->unique(['org_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_delivery_zones');
    }
};
