<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('communities', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->char('created_by', 26);
            $table->char('org_id', 26)->nullable();
            $table->string('status', 50)->default('active');
            $table->boolean('is_public')->default(false);   // discoverable community
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            $table->index('org_id');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('communities');
    }
};
