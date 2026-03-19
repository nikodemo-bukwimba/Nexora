<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('actor_type_assignments', function (Blueprint $table) {
            $table->char('actor_id', 26);
            $table->char('actor_type_id', 26);
            $table->timestamp('assigned_at')->useCurrent();
            $table->char('assigned_by', 26)->nullable(); // null = system

            $table->primary(['actor_id', 'actor_type_id']);

            $table->foreign('actor_id')
                  ->references('id')->on('actors')
                  ->onDelete('cascade');

            $table->foreign('actor_type_id')
                  ->references('id')->on('actor_types');

            $table->foreign('assigned_by')
                  ->references('id')->on('actors')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('actor_type_assignments');
    }
};
