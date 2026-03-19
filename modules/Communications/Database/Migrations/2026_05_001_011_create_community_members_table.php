<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('community_members', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('community_id', 26);
            $table->char('actor_id', 26);
            $table->string('role', 50)->default('member'); // member|admin|super_admin
            $table->string('status', 50)->default('active'); // active|left|removed
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();

            $table->unique(['community_id', 'actor_id']);
            $table->index('community_id');

            $table->foreign('community_id')
                  ->references('id')->on('communities')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('community_members');
    }
};
