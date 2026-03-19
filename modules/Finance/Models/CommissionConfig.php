<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionConfig extends FinanceModel
{
    protected $table    = 'commission_configs';
    protected $fillable = [
        'name', 'rate', 'is_active', 'is_default',
        'effective_from', 'effective_until', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rate'             => 'decimal:6',
            'is_active'        => 'boolean',
            'is_default'       => 'boolean',
            'effective_from'   => 'datetime',
            'effective_until'  => 'datetime',
        ];
    }

    public function records(): HasMany
    {
        return $this->hasMany(CommissionRecord::class, 'commission_config_id');
    }
}
