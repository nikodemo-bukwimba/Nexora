<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'communications';

    public function up(): void
    {
        Schema::connection('communications')->create('broadcasts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 255);
            $table->char('owner_actor_id', 26);
            $table->char('org_id', 26)->nullable();
            $table->string('status', 50)->default('active'); // active|archived
            $table->timestamps();

            $table->index('owner_actor_id');
            $table->index('org_id');
        });
    }

    public function down(): void
    {
        Schema::connection('communications')->dropIfExists('broadcasts');
    }
};
