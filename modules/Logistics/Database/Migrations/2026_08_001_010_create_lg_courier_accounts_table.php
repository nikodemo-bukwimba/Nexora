<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        Schema::connection('logistics')->create('lg_courier_accounts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('org_id', 26);
            $table->string('courier', 50);                       // dhl|g4s|sendy|other
            $table->string('name', 100);                         // display name
            $table->string('account_number', 100)->nullable();
            $table->text('api_key_encrypted')->nullable();       // encrypted at rest
            $table->text('api_secret_encrypted')->nullable();
            $table->jsonb('settings')->nullable();               // courier-specific config
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('org_id');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_courier_accounts');
    }
};
