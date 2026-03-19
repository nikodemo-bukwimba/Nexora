<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('community_groups', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('community_id', 26);
            $table->char('group_id', 26);
            $table->boolean('is_announcement_channel')->default(false);
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['community_id', 'group_id']);
            $table->index('community_id');

            $table->foreign('community_id')
                  ->references('id')->on('communities')
                  ->onDelete('cascade');

            $table->foreign('group_id')
                  ->references('id')->on('groups')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('community_groups');
    }
};
