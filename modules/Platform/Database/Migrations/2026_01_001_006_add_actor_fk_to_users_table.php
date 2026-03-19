<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->table('users', function (Blueprint $table) {
            $table->foreign('actor_id')
                  ->references('id')->on('actors')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->table('users', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
        });
    }
};
