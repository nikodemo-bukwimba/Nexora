<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('organizations', function (Blueprint $table) {
            $table->char('id', 26)->primary();

            /*
            |--------------------------------------------------------------------------
            | Actor Ownership
            |--------------------------------------------------------------------------
            */

            $table->char('actor_id', 26)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Organization Hierarchy
            |--------------------------------------------------------------------------
            |
            | parent_id:
            |     Direct parent organization.
            |
            | root_org_id:
            |     Top-most tenant/root organization.
            |
            | path:
            |     PostgreSQL LTREE hierarchy path.
            |--------------------------------------------------------------------------
            */

            $table->char('parent_id', 26)
                ->nullable()
                ->index();

            $table->char('root_org_id', 26)
                ->nullable()
                ->index();

            $table->string('path', 500);

            $table->smallInteger('depth')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Organization Identity
            |--------------------------------------------------------------------------
            */

            $table->string('name', 255);

            $table->string('slug', 255)
                ->unique();

            // root | branch | department | vendor etc
            $table->string('type', 50)
                ->default('root');

            /*
            |--------------------------------------------------------------------------
            | Approval Workflow
            |--------------------------------------------------------------------------
            */

            // pending_approval | active | rejected | suspended
            $table->string('status', 50)
                ->default('pending_approval')
                ->index();

            $table->char('approved_by', 26)
                ->nullable();

            $table->timestamp('approved_at')
                ->nullable();

            $table->text('rejection_reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Settings
            |--------------------------------------------------------------------------
            */

            $table->jsonb('settings')
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
                ->on('actors');

            $table->foreign('parent_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete();

            $table->foreign('root_org_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | PostgreSQL LTREE Conversion + Index
        |--------------------------------------------------------------------------
        */

        DB::connection('platform')->statement(
            'ALTER TABLE organizations 
             ALTER COLUMN path TYPE ltree 
             USING path::ltree'
        );

        DB::connection('platform')->statement(
            'CREATE INDEX idx_organizations_path 
             ON organizations 
             USING GIST (path)'
        );
    }

    public function down(): void
    {
        Schema::connection('platform')
            ->dropIfExists('organizations');
    }
};