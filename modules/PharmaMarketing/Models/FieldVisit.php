<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldVisit extends PharmaModel
{
    protected $table    = 'pm_field_visits';
    protected $fillable = [
        'org_id', 'customer_id', 'officer_actor_id', 'weekly_plan_item_id',
        'status', 'visit_type',
        'check_in_at', 'check_out_at', 'duration_minutes',
        'check_in_latitude', 'check_in_longitude', 'check_in_gps_accuracy_meters',
        'check_out_latitude', 'check_out_longitude',
        'objective', 'discussion_summary', 'outcome', 'outcome_status',
        'follow_up_notes', 'follow_up_date',
        'contact_person_id', 'contact_person_name',
        'notes', 'metadata',
        // ── Admin review ──────────────────────────────────────
        'admin_status', 'flag_reason', 'admin_notes',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at'    => 'datetime',
            'check_out_at'   => 'datetime',
            'follow_up_date' => 'date',
            'metadata'       => 'array',
            'reviewed_at'    => 'datetime',   // ← add
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VisitAttachment::class, 'visit_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(VisitProduct::class, 'visit_id');
    }

    public function planItem(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlanItem::class, 'weekly_plan_item_id');
    }

    public function isCompleted(): bool   { return $this->status === 'completed'; }
    public function isInProgress(): bool  { return $this->status === 'in_progress'; }

    public function computeDuration(): int
    {
        if ($this->check_in_at && $this->check_out_at) {
            return (int) $this->check_in_at->diffInMinutes($this->check_out_at);
        }
        return 0;
    }
}
