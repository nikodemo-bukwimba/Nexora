<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'notifications';

    public function up(): void
    {
        Schema::connection('notifications')->create('notification_preferences', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('actor_id', 26);
            $table->string('type', 100);              // notification type e.g. order.shipped
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->timestamps();

            $table->unique(['actor_id', 'type']);
            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::connection('notifications')->dropIfExists('notification_preferences');
    }
};
