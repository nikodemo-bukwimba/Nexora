<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('actor_relationships', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('actor_id', 26);
            $table->char('related_actor_id', 26);
            $table->string('relationship_type', 100);  // derived from the interaction type
            $table->string('source_module', 100);       // which module triggered this
            $table->string('source_event', 150);        // which event created it
            $table->string('direction', 20)->default('bilateral'); // bilateral|unilateral
            $table->string('status', 50)->default('active');       // active|pending|revoked
            $table->jsonb('metadata')->nullable();
            $table->timestamp('initiated_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable(); // null if unilateral or pending
            $table->timestamps();

            // Prevents duplicate relationships of same type from same module
            $table->unique(['actor_id', 'related_actor_id', 'relationship_type', 'source_module']);

            $table->foreign('actor_id')
                  ->references('id')->on('actors');

            $table->foreign('related_actor_id')
                  ->references('id')->on('actors');

            $table->index('actor_id');
            $table->index('related_actor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('actor_relationships');
    }
};
