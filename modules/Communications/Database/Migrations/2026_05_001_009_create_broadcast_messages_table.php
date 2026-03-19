<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('broadcast_messages', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('broadcast_id', 26);
            $table->char('sender_actor_id', 26);
            $table->text('content')->nullable();
            $table->string('content_type', 50)->default('text');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('broadcast_id');
            $table->index('created_at');

            $table->foreign('broadcast_id')
                  ->references('id')->on('broadcasts')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('broadcast_messages');
    }
};
