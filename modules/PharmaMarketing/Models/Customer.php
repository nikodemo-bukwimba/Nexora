<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class Customer extends Model
{
    use HasUlid, SoftDeletes;

    protected $connection = 'pharma_marketing';
    protected $table      = 'pm_customers';

    protected $fillable = [
        'org_id',
        'home_branch_id',
        'assigned_officer_id',
        'platform_user_id',
        'actor_id',              // ← added
        'registration_source',
        'customer_type',
        'name',
        'code',
        'category',
        'tier',
        'status',
        'business_registration',
        'tax_pin',
        'address',
        'street',
        'ward',
        'city',
        'county',
        'country',
        'latitude',
        'longitude',
        'gps_accuracy_meters',
        'phone',
        'alt_phone',
        'email',
        'whatsapp_number',
        'receives_whatsapp',
        'receives_sms',
        'receives_in_app',
        'credit_limit',
        'currency',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'receives_whatsapp' => 'boolean',
            'receives_sms'      => 'boolean',
            'receives_in_app'   => 'boolean',
            'credit_limit'      => 'decimal:4',
            'latitude'          => 'decimal:7',
            'longitude'         => 'decimal:7',
            'metadata'          => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class, 'customer_id');
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(CustomerContact::class, 'customer_id')
            ->where('is_primary', true)
            ->latestOfMany();
    }

    public function visits(): HasMany
    {
        return $this->hasMany(FieldVisit::class, 'customer_id')
            ->orderBy('check_in_at', 'desc');
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isB2B(): bool
    {
        return $this->customer_type === 'b2b';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    public function scopeAssignedTo($query, string $officerId)
    {
        return $query->where('assigned_officer_id', $officerId);
    }
}