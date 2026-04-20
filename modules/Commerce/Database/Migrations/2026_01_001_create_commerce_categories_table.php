<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'commerce';

    public function up(): void {
        DB::connection('commerce')->statement('CREATE SCHEMA IF NOT EXISTS commerce');
        Schema::connection('commerce')->create('categories', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26)->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['org_id', 'is_active']);
        });
    }

    public function down(): void {
        Schema::connection('commerce')->dropIfExists('categories');
    }
};