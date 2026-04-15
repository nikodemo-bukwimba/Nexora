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
        Schema::connection('platform')->table('org_roles', function (Blueprint $table) {
            $table->string('slug', 100)->nullable()->after('name');
            $table->index('slug');
        });

        DB::connection('platform')->table('org_roles')->get()->each(function ($role) {
            $slug = strtolower(preg_replace('/\s+/', '_', trim($role->name)));
            DB::connection('platform')->table('org_roles')
                ->where('id', $role->id)
                ->update(['slug' => $slug]);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->table('org_roles', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
