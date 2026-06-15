<?php

// === FILE: Modules/Delivery/Database/Migrations/2026_08_002_001_add_invoice_fields_to_deliveries.php
//
//  Adds the full invoice confirmation payload to the deliveries table:
//    1. invoice_date          — date on the physical invoice
//    2. invoice_number        — invoice reference number
//    3. invoice_value         — amount on the invoice
//    4. invoice_comment       — customer feedback / complaint / comment
//    5. signed_invoice_path   — URL of the uploaded signed invoice document
//
//  Items 1–3 are already consumed by the Flutter customer app (confirmDelivery).
//  Items 4–5 are new additions introduced in this migration.
// ================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'delivery';

    public function up(): void
    {
        Schema::connection('delivery')->table('deliveries', function (Blueprint $table) {
            // ── Invoice reference fields (1–3) ──────────────────
            // Added here — the table was created without them.
            $table->string('invoice_number', 100)->nullable()->after('cancelled_at');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->decimal('invoice_value', 14, 2)->nullable()->after('invoice_date');

            // ── Invoice comment / feedback / complaint (4) ──────
            $table->text('invoice_comment')->nullable()->after('invoice_value');

            // ── Signed invoice document URL (5) ────────────────
            // Populated by POST .../confirm (multipart upload).
            $table->string('signed_invoice_path', 1000)->nullable()->after('invoice_comment');
        });
    }

    public function down(): void
    {
        Schema::connection('delivery')->table('deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number',
                'invoice_date',
                'invoice_value',
                'invoice_comment',
                'signed_invoice_path',
            ]);
        });
    }
};