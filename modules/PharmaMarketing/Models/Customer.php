<?php
// FILE: modules/PharmaMarketing/Models/Customer.php
// CHANGE: Added 'home_branch_id' to fillable.
// Everything else unchanged.

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends PharmaModel
{
    use SoftDeletes;

    protected $table    = 'pm_customers';
    protected $fillable = [
        'org_id',
        'home_branch_id',          // ← NEW: which branch originally created this customer
        'assigned_officer_id',
        'platform_user_id',
        'registration_source',
        'customer_type', 'name', 'code',
        'category', 'tier', 'status',
        'business_registration', 'tax_pin',
        'address', 'city', 'county', 'country',
        'latitude', 'longitude', 'gps_accuracy_meters',
        'phone', 'alt_phone', 'email', 'whatsapp_number',
        'receives_whatsapp', 'receives_sms', 'receives_in_app',
        'credit_limit', 'currency', 'notes', 'metadata',
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

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class, 'customer_id');
    }

    public function primaryContact(): ?CustomerContact
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    public function visits(): HasMany
    {
        return $this->hasMany(FieldVisit::class, 'customer_id')->orderBy('check_in_at', 'desc');
    }

    public function isB2B(): bool    { return $this->customer_type === 'b2b'; }
    public function isActive(): bool { return $this->status === 'active'; }
}
