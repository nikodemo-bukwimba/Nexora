<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('message_reactions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('message_type', 50); // DirectMessage|GroupMessage|BroadcastMessage
            $table->char('message_id', 26);
            $table->char('actor_id', 26);
            $table->string('emoji', 10);         // e.g. 👍 ❤️ 😂
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['message_type', 'message_id', 'actor_id']);
            $table->index(['message_type', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('message_reactions');
    }
};
