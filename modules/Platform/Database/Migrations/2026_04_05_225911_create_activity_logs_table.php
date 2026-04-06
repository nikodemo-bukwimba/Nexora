<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('activity_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('org_id', 26)->index();
            $table->string('actor_id', 26)->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('actor_role')->nullable();
            $table->string('action', 100)->index();        // created|updated|deleted|transitioned|logged_in
            $table->string('entity_type', 100)->index();   // order|product|customer|officer|payment...
            $table->string('entity_id', 26)->nullable()->index();
            $table->jsonb('entity_snapshot')->nullable();  // before/after or relevant fields
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('activity_logs');
    }
};