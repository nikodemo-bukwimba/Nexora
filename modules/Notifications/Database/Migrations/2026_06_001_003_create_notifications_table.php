<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'notifications';

    public function up(): void
    {
        Schema::connection('notifications')->create('notifications', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // Recipient
            $table->char('actor_id', 26);
            // Trigger context
            $table->string('type', 100);                     // order.shipped|payment.received|message.received|etc
            $table->string('title', 255);
            $table->text('body');
            $table->string('channel', 50)->default('push');  // push|email|sms
            // Deep link data
            $table->string('action_url', 500)->nullable();    // e.g. /orders/01XYZ or /messages/01ABC
            $table->string('ref_type', 100)->nullable();      // Order|Payment|OrgInvitation|Announcement
            $table->char('ref_id', 26)->nullable();
            $table->jsonb('data')->nullable();                 // extra payload for client
            // Status
            $table->string('status', 50)->default('pending'); // pending|sent|delivered|failed|read
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('failure_reason')->nullable();
            // Immutable — append only
            $table->timestamp('created_at')->useCurrent();

            $table->index('actor_id');
            $table->index('type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('notifications')->dropIfExists('notifications');
    }
};
