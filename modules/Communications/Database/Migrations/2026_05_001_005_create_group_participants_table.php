<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('group_participants', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('group_id', 26);
            $table->char('actor_id', 26);
            $table->string('role', 50)->default('member'); // member|admin|super_admin
            $table->boolean('muted')->default(false);
            $table->boolean('archived')->default(false);
            $table->timestamp('muted_until')->nullable();
            $table->char('added_by', 26)->nullable();
            $table->string('status', 50)->default('active'); // active|left|removed
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'actor_id']);
            $table->index('group_id');
            $table->index('actor_id');

            $table->foreign('group_id')
                  ->references('id')->on('groups')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('group_participants');
    }
};
