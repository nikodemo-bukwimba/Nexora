<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierAccount extends LogisticsModel
{
    protected $table    = 'lg_courier_accounts';
    protected $fillable = [
        'org_id', 'courier', 'name', 'account_number',
        'api_key_encrypted', 'api_secret_encrypted',
        'settings', 'is_active', 'is_default',
    ];

    protected $hidden = ['api_key_encrypted', 'api_secret_encrypted'];

    protected function casts(): array
    {
        return [
            'settings'   => 'array',
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(CourierShipment::class, 'courier_account_id');
    }
}
