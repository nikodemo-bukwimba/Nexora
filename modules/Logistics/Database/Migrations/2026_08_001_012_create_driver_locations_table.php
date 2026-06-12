// Modules/Logistics/Database/Migrations/2026_05_31_000001_create_driver_locations_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        Schema::connection('logistics')->create('lg_driver_locations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('driver_id', 26)->index();
            $table->char('run_id', 26)->nullable()->index();
            $table->char('stop_id', 26)->nullable()->index(); // current stop being navigated to
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('speed_kmh', 6, 2)->nullable();
            $table->decimal('heading_degrees', 5, 2)->nullable();
            $table->string('source', 20)->default('gps'); // gps|network|passive
            $table->timestamp('recorded_at')->useCurrent()->index();
            // No updated_at — append-only location log
            $table->timestamp('created_at')->useCurrent();

            $table->index(['driver_id', 'recorded_at']);
            $table->index(['run_id', 'recorded_at']);
        });

        // Separate table for last-known position per driver (denormalized for speed)
        Schema::connection('logistics')->create('lg_driver_last_positions', function (Blueprint $table) {
            $table->char('driver_id', 26)->primary();
            $table->char('run_id', 26)->nullable();
            $table->char('stop_id', 26)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('speed_kmh', 6, 2)->nullable();
            $table->decimal('heading_degrees', 5, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_driver_last_positions');
        Schema::connection('logistics')->dropIfExists('lg_driver_locations');
    }
};