<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('organizations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('actor_id', 26)->unique();
            $table->char('parent_id', 26)->nullable();
            $table->char('root_org_id', 26)->nullable();
            // ltree path — cast via raw statement after table creation
            $table->string('path', 500);
            $table->smallInteger('depth')->default(0);
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('type', 50)->default('root');     // root|branch
            $table->string('status', 50)->default('active'); // active|suspended|inactive
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('root_org_id');
            $table->index('parent_id');
            $table->index('status');

            // actor_id FK — actors table already exists, safe here
            $table->foreign('actor_id')
                  ->references('id')->on('actors');
        });

        // Cast path column to ltree and add GiST index
        DB::connection('platform')->statement(
            'ALTER TABLE organizations ALTER COLUMN path TYPE ltree USING path::ltree'
        );
        DB::connection('platform')->statement(
            'CREATE INDEX idx_organizations_path ON organizations USING GIST (path)'
        );

        // Self-referential FKs added in migration 009b after
        // the primary key is fully committed by PostgreSQL
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('organizations');
    }
};
