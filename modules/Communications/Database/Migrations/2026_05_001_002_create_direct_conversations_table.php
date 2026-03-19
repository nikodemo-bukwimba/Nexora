<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('direct_conversations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('initiator_actor_id', 26);
            $table->char('recipient_actor_id', 26);
            $table->char('last_message_id', 26)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('initiator_archived')->default(false);
            $table->boolean('recipient_archived')->default(false);
            $table->boolean('initiator_muted')->default(false);
            $table->boolean('recipient_muted')->default(false);
            $table->integer('retention_days')->default(0); // 0 = forever
            $table->string('status', 50)->default('active'); // active|blocked
            $table->timestamps();

            // Ensure only one conversation per actor pair
            $table->unique(['initiator_actor_id', 'recipient_actor_id']);
            $table->index('initiator_actor_id');
            $table->index('recipient_actor_id');
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('direct_conversations');
    }
};
