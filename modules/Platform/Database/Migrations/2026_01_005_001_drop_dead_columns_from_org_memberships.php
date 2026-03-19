<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->table('org_memberships', function (Blueprint $table) {
            $table->dropColumn(['invite_token', 'invite_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->table('org_memberships', function (Blueprint $table) {
            $table->string('invite_token', 64)->nullable();
            $table->timestamp('invite_expires_at')->nullable();
        });
    }
};
