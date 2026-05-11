<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        // Only seed the feature flag key — the value (org ULID) must be
        // updated via the admin API after the org is created.
        // The Customer role is created automatically by AuthService
        // when a customer self-registers.
        DB::connection('platform')->table('platform_feature_flags')->insertOrIgnore([
            'id'          => (string) new Ulid(),
            'key'         => 'platform.default_org_id',
            'value'       => false, // disabled until admin sets the real org ULID
            'description' => 'Root org ULID for auto-enrolling self-registered customers. Set via admin API after org is created.',
            'module'      => 'platform',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::connection('platform')
            ->table('platform_feature_flags')
            ->where('key', 'platform.default_org_id')
            ->delete();
    }
};