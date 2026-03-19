<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_invitations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->char('org_role_id', 26);
            $table->smallInteger('level')->default(0);
            $table->string('email', 255);
            $table->string('token', 64)->unique();
            $table->char('invited_by', 26);
            $table->string('status', 50)->default('pending'); // pending|accepted|expired|cancelled
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index('org_id');

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('org_role_id')->references('id')->on('org_roles')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_invitations');
    }
};
