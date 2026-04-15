<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        // Immutable log — every stop status change is recorded
        Schema::connection('logistics')->create('lg_stop_status_logs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('stop_id', 26);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->char('changed_by', 26)->nullable();          // actor_id (driver or dispatcher)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('stop_id');
            $table->index('created_at');

            $table->foreign('stop_id')
                  ->references('id')->on('lg_delivery_stops')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_stop_status_logs');
    }
};
