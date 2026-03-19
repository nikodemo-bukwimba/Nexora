<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('platform_feature_flags', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('key', 150)->unique(); // marketplace.enabled|social.enabled
            $table->boolean('value')->default(false);
            $table->text('description')->nullable();
            $table->string('module', 100)->nullable();
            $table->char('updated_by', 26)->nullable();
            $table->timestamps();

            $table->foreign('updated_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_feature_flags');
    }
};
