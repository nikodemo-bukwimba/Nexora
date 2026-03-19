<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        // Tracks delivery + read status per actor per message (used for groups/broadcasts)
        Schema::connection('communications')->create('message_receipts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('message_type', 50); // GroupMessage|BroadcastMessage
            $table->char('message_id', 26);
            $table->char('actor_id', 26);
            $table->timestamp('delivered_at')->nullable(); // single tick → double tick
            $table->timestamp('read_at')->nullable();      // double tick → blue tick
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['message_type', 'message_id', 'actor_id']);
            $table->index(['message_type', 'message_id']);
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('message_receipts');
    }
};
