<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    public function up(): void
    {
        Schema::connection('finance')->create('commission_configs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 100)->default('default');
            $table->decimal('rate', 8, 6);                  // e.g. 0.050000 = 5%
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);  // one default config at a time
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_until')->nullable(); // null = no expiry
            $table->char('created_by', 26)->nullable();     // soft FK to platform user
            $table->timestamps();

            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::connection('finance')->dropIfExists('commission_configs');
    }
};
