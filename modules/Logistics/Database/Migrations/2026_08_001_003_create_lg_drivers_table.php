<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        Schema::connection('logistics')->create('lg_drivers', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->char('actor_id', 26)->unique();              // soft FK → platform actor
            $table->string('name', 255);
            $table->string('phone', 30)->nullable();
            $table->string('license_number', 100)->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('status', 50)->default('active');    // active|inactive|suspended
            $table->string('availability', 50)->default('offline'); // online|offline|on_run
            $table->timestamp('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('actor_id');
            $table->index('availability');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_drivers');
    }
};
