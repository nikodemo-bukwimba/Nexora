<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends PharmaModel
{
    protected $table    = 'pm_customer_contacts';
    protected $fillable = [
        'customer_id', 'name', 'role', 'phone',
        'email', 'whatsapp_number', 'is_primary', 'notes',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
