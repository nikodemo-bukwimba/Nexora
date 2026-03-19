<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        // Polymorphic — shared across direct_messages, group_messages, broadcast_messages
        Schema::connection('communications')->create('message_attachments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('message_type', 50); // DirectMessage|GroupMessage|BroadcastMessage
            $table->char('message_id', 26);
            $table->string('type', 50);         // image|document|audio|video
            $table->string('file_name', 255)->nullable();
            $table->string('file_url', 1000);   // URL in object storage or Laravel storage
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->integer('duration_seconds')->nullable(); // for audio/video
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('thumbnail_url', 1000)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['message_type', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('message_attachments');
    }
};
