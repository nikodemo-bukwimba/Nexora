<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('org_permission_definitions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('name', 150)->unique(); // inventory.delete|orders.approve
            $table->string('group_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('group_name');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('org_permission_definitions');
    }
};
