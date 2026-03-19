<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('groups', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->char('created_by', 26);               // actor_id
            $table->char('org_id', 26)->nullable();        // null = personal group
            $table->char('community_id', 26)->nullable();  // null = standalone group
            $table->string('type', 50)->default('group'); // group|channel
            $table->string('status', 50)->default('active'); // active|archived|suspended
            $table->integer('max_participants')->default(1024);
            $table->integer('retention_days')->default(0);
            $table->boolean('only_admins_can_message')->default(false);
            $table->boolean('only_admins_can_edit_info')->default(true);
            $table->char('last_message_id', 26)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('community_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('groups');
    }
};
