<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('group_messages', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('group_id', 26);
            $table->char('sender_actor_id', 26);
            $table->text('content')->nullable();            // encrypted ciphertext
            $table->string('content_type', 50)->default('text');
            $table->char('reply_to_id', 26)->nullable();
            $table->char('forwarded_from_id', 26)->nullable();
            $table->boolean('forwarded')->default(false);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('deleted_for_everyone')->default(false);
            $table->timestamp('deleted_at')->nullable();
            // System messages (member joined, group name changed, etc.)
            $table->boolean('is_system_message')->default(false);
            $table->string('system_event', 100)->nullable(); // member_joined|member_left|name_changed
            $table->timestamp('created_at')->useCurrent();

            $table->index('group_id');
            $table->index('sender_actor_id');
            $table->index('created_at');

            $table->foreign('group_id')
                  ->references('id')->on('groups')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('group_messages');
    }
};
