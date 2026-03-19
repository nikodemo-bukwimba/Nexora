<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('direct_messages', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('conversation_id', 26);
            $table->char('sender_actor_id', 26);
            // E2E encrypted — platform stores ciphertext only
            $table->text('content')->nullable();           // encrypted ciphertext
            $table->string('content_type', 50)->default('text'); // text|image|document|audio|location|forwarded
            $table->char('reply_to_id', 26)->nullable();  // threaded reply
            $table->char('forwarded_from_id', 26)->nullable();
            $table->boolean('forwarded')->default(false);
            // Location payload (only for location type)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Deletion flags
            $table->boolean('deleted_for_sender')->default(false);
            $table->boolean('deleted_for_recipient')->default(false);
            $table->boolean('deleted_for_everyone')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->string('status', 50)->default('sent'); // sent|delivered|read
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            // Immutable — append only
            $table->timestamp('created_at')->useCurrent();

            $table->index('conversation_id');
            $table->index('sender_actor_id');
            $table->index('created_at');

            $table->foreign('conversation_id')
                  ->references('id')->on('direct_conversations')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('direct_messages');
    }
};
