<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('event_dispatch_log', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('event_name', 200);
            $table->string('module', 100);
            $table->jsonb('payload');
            $table->char('actor_id', 26)->nullable();
            $table->string('dispatch_mode', 20); // sync|async
            $table->string('status', 50)->default('dispatched'); // dispatched|failed|replayed
            $table->timestamp('fired_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->index('event_name');
            $table->index('fired_at');
            $table->index('actor_id');
            $table->index('status');

            $table->foreign('actor_id')
                  ->references('id')->on('actors')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('event_dispatch_log');
    }
};
