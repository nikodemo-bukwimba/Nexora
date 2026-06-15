<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->table('message_attachments', function (Blueprint $table) {
            $table->char('message_id', 26)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->table('message_attachments', function (Blueprint $table) {
            $table->char('message_id', 26)->nullable(false)->change();
        });
    }
};