<?php

// === FILE: Modules/Delivery/Database/Migrations/2026_08_003_001_add_creator_fields_to_deliveries.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'delivery';

    public function up(): void
    {
        Schema::connection('delivery')->table('deliveries', function (Blueprint $table) {
            // Platform user ID (char 26 ULID) of whoever created this delivery.
            // Nullable — deliveries created before this migration have no creator.
            $table->char('created_by_id', 26)->nullable()->after('org_id');

            // Denormalised display name at creation time.
            // Stored here so the name is stable even if the user's
            // actor display_name changes later.
            $table->string('created_by_name', 255)->nullable()->after('created_by_id');

            // Role slug at creation time (e.g. 'org_admin', 'manager', 'staff').
            // Lets the detail page show "Created by Jane — Branch Manager"
            // without a cross-schema join on every read.
            $table->string('created_by_role', 100)->nullable()->after('created_by_name');

            $table->index('created_by_id');
        });
    }

    public function down(): void
    {
        Schema::connection('delivery')->table('deliveries', function (Blueprint $table) {
            $table->dropIndex(['created_by_id']);
            $table->dropColumn(['created_by_id', 'created_by_name', 'created_by_role']);
        });
    }
};