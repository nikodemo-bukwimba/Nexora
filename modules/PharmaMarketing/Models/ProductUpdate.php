<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUpdate extends PharmaModel
{
    protected $table    = 'pm_product_updates';
    protected $fillable = [
        'org_id', 'created_by', 'title', 'body', 'update_type',
        'target_segment', 'target_filters',
        'send_in_app', 'send_whatsapp', 'send_sms',
        'product_ids', 'media_url', 'media_type',
        'status', 'scheduled_at', 'sent_at',
        'total_recipients', 'sent_count', 'failed_count',
        'start_date', 'end_date',   // ← ADD
    ];

    protected function casts(): array
    {
        return [
            'target_filters' => 'array',
            'product_ids'    => 'array',
            'send_in_app'    => 'boolean',
            'send_whatsapp'  => 'boolean',
            'send_sms'       => 'boolean',
            'scheduled_at'   => 'datetime',
            'sent_at'        => 'datetime',
            'start_date'     => 'date',    // ← ADD
            'end_date'       => 'date',    // ← ADD
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ProductUpdateDelivery::class, 'product_update_id');
    }

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isSent(): bool  { return $this->status === 'sent'; }
}
