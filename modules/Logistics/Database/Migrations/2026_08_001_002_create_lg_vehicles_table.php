<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        Schema::connection('logistics')->create('lg_vehicles', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->string('registration', 50)->unique();       // number plate
            $table->string('type', 50)->default('van');         // truck|van|motorcycle|bicycle
            $table->string('make', 100)->nullable();            // Toyota, Isuzu, etc.
            $table->string('model', 100)->nullable();
            $table->integer('year')->nullable();
            $table->decimal('payload_kg', 10, 2)->nullable();   // max load capacity
            $table->integer('max_stops')->nullable();           // max stops per run
            $table->string('status', 50)->default('active');   // active|maintenance|retired
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_vehicles');
    }
};
