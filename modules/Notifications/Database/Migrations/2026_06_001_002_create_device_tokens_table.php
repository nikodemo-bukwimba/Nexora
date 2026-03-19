<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'notifications';

    public function up(): void
    {
        Schema::connection('notifications')->create('device_tokens', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // Soft FK — actor lives in platform schema
            $table->char('actor_id', 26);
            $table->string('token', 500)->unique();         // FCM/APNS/Web Push token
            $table->string('platform', 50);                  // android|ios|web
            $table->string('driver', 50)->default('fcm');    // fcm|apns|web-push
            $table->string('device_name', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('actor_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('notifications')->dropIfExists('device_tokens');
    }
};
