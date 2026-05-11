<?php
// FILE: modules/PharmaMarketing/Models/PmOfficer.php
// CHANGE: Added three fillable fields, 'transferred_at' cast, and two transfer helpers.

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PmOfficer extends PharmaModel
{
    use SoftDeletes;

    protected $table = 'pm_officers';

    protected $fillable = [
        'org_id', 'branch_id', 'platform_user_id', 'actor_id',
        'registration_source', 'name', 'email', 'phone', 'status', 'metadata',
        // ── NEW ──────────────────────────────────────────────────────────
        'previous_branch_id', 'transferred_at', 'transferred_by',
        // ─────────────────────────────────────────────────────────────────
    ];

    protected function casts(): array
    {
        return [
            'metadata'       => 'array',
            'transferred_at' => 'datetime', // ── NEW ──
        ];
    }

    public function isSelfRegistered(): bool
    {
        return $this->registration_source === 'self_registered';
    }

    // ── NEW: Transfer helpers ─────────────────────────────────────────

    /**
     * Move this officer to a new branch and record the audit trail.
     * Call inside a DB transaction (handled by OfficerService).
     */
    public function transferToBranch(string $newBranchId, string $transferredBy): void
    {
        $this->update([
            'previous_branch_id' => $this->branch_id,
            'branch_id'          => $newBranchId,
            'transferred_at'     => now(),
            'transferred_by'     => $transferredBy,
        ]);
    }

    public function hasBeenTransferred(): bool
    {
        return $this->previous_branch_id !== null;
    }
}
