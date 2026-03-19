<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('broadcast_recipients', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('broadcast_id', 26);
            $table->char('actor_id', 26);
            $table->string('status', 50)->default('active'); // active|removed
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['broadcast_id', 'actor_id']);
            $table->index('broadcast_id');

            $table->foreign('broadcast_id')
                  ->references('id')->on('broadcasts')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('broadcast_recipients');
    }
};
