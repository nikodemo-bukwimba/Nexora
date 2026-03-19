<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('actors', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('display_name', 255);
            $table->text('avatar_url')->nullable();
            $table->jsonb('metadata')->nullable();  // extensible per actor nature
            $table->string('status', 50)->default('active'); // active|suspended|inactive
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('actors');
    }
};
