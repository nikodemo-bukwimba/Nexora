<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUpdateDelivery extends PharmaModel
{
    public $timestamps  = false;
    protected $table    = 'pm_product_update_deliveries';
    protected $fillable = [
        'product_update_id', 'customer_id', 'channel',
        'status', 'recipient_address', 'external_message_id',
        'failure_reason', 'retry_count',
        'sent_at', 'delivered_at', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'      => 'datetime',
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
            'created_at'   => 'datetime',
        ];
    }

    public function update(): BelongsTo
    {
        return $this->belongsTo(ProductUpdate::class, 'product_update_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
