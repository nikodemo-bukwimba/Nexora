<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->table('organizations', function (Blueprint $table) {
            $table->foreign('parent_id')
                  ->references('id')->on('organizations')
                  ->onDelete('restrict');

            $table->foreign('root_org_id')
                  ->references('id')->on('organizations')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->table('organizations', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['root_org_id']);
        });
    }
};
