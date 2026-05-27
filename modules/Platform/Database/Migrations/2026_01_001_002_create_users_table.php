<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('users', function (Blueprint $table) {
            $table->char('id', 26)->primary();

            /*
            |--------------------------------------------------------------------------
            | Authentication
            |--------------------------------------------------------------------------
            */

            $table->string('username', 50)
                ->unique();

            $table->string('email', 255)
                ->unique();

            $table->timestamp('email_verified_at')
                ->nullable();

            $table->string('password', 255)
                ->nullable();

            $table->string('remember_token', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Two Factor Authentication
            |--------------------------------------------------------------------------
            */

            $table->text('two_factor_secret')
                ->nullable();

            $table->text('two_factor_recovery_codes')
                ->nullable();

            $table->timestamp('two_factor_confirmed_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Actor Relationship
            |--------------------------------------------------------------------------
            |
            | Links user account to actor profile.
            |--------------------------------------------------------------------------
            */

            $table->char('actor_id', 26)
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Status & Security
            |--------------------------------------------------------------------------
            */

            // active | suspended | blocked | archived
            $table->string('status', 50)
                ->default('active');

            $table->timestamp('last_login_at')
                ->nullable();

            $table->string('last_login_ip', 45)
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('actor_id')
                ->references('id')
                ->on('actors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('users');
    }
};