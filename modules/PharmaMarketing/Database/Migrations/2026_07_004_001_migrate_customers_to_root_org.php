<?php
// FILE: modules/PharmaMarketing/Database/Migrations/2026_07_004_001_migrate_customers_to_root_org.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        // Step 1: add home_branch_id to preserve original branch ownership
        Schema::connection('pharma_marketing')->table('pm_customers', function (Blueprint $table) {
            $table->char('home_branch_id', 26)->nullable()->index()->after('org_id');
        });

        // Step 2 & 3:
        // Move customers from branch org -> root org
        // while preserving original branch in home_branch_id

        // Read branches from platform DB
        $branchOrgs = DB::connection('platform')
            ->table('organizations')
            ->where('type', 'branch')
            ->get(['id', 'root_org_id']);

        foreach ($branchOrgs as $branch) {
            DB::connection('pharma_marketing')
                ->table('pm_customers')
                ->where('org_id', $branch->id)
                ->update([
                    'home_branch_id' => $branch->id,
                    'org_id'         => $branch->root_org_id,
                ]);
        }

        // Step 4: optionally drop/recreate unique constraints if needed
        // Schema::connection('pharma_marketing')->table('pm_customers', function (Blueprint $table) {
        //     $table->dropUnique(['org_id', 'platform_user_id']);
        // });
    }

    public function down(): void
    {
        // Restore original branch org_id
        DB::connection('pharma_marketing')
            ->table('pm_customers')
            ->whereNotNull('home_branch_id')
            ->update([
                'org_id' => DB::raw('home_branch_id'),
            ]);

        Schema::connection('pharma_marketing')->table('pm_customers', function (Blueprint $table) {
            $table->dropColumn('home_branch_id');
        });
    }
};