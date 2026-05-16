<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\PmOfficer;

class BackfillPmOfficers extends Command
{
    protected $signature   = 'backfill:pm-officers';
    protected $description = 'Create missing pm_officers records for existing branch members';

    public function handle(): void
    {
        $members = DB::connection('platform')
            ->table('org_memberships as om')
            ->join('organizations as o', 'o.id', '=', 'om.org_id')
            ->join('users as u', 'u.id', '=', 'om.user_id')
            ->join('actors as a', 'a.id', '=', 'u.actor_id')
            ->where('om.status', 'active')
            ->where('o.type', 'branch')
            ->select('u.id as user_id', 'u.actor_id', 'u.email', 'o.id as branch_id', 'o.root_org_id', 'a.display_name')
            ->get()
            ->filter(fn($m) => !PmOfficer::where('platform_user_id', $m->user_id)->exists());

        if ($members->isEmpty()) {
            $this->info('No missing pm_officers records found.');
            return;
        }

        foreach ($members as $m) {
            PmOfficer::create([
                'org_id'              => $m->root_org_id,
                'branch_id'           => $m->branch_id,
                'platform_user_id'    => $m->user_id,
                'actor_id'            => $m->actor_id,
                'registration_source' => 'admin',
                'name'                => $m->display_name,
                'email'               => $m->email,
                'status'              => 'active',
            ]);
            $this->info("Created pm_officer for {$m->display_name} ({$m->email})");
        }

        $this->info('Done.');
    }
}