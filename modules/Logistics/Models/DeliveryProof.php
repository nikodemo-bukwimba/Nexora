<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryProof extends LogisticsModel
{
    public $timestamps  = false;
    protected $table    = 'lg_delivery_proofs';
    protected $fillable = [
        'stop_id', 'photo_url', 'photo_latitude', 'photo_longitude',
        'signature_url', 'signed_by_name',
        'confirmation_code', 'code_confirmed_at',
        'captured_by',
    ];

    protected function casts(): array
    {
        return [
            'code_confirmed_at' => 'datetime',
            'captured_at'       => 'datetime',
        ];
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(DeliveryStop::class, 'stop_id');
    }

    public function hasPhoto(): bool     { return ! empty($this->photo_url); }
    public function hasSignature(): bool { return ! empty($this->signature_url); }
    public function hasCode(): bool      { return ! empty($this->confirmation_code) && $this->code_confirmed_at; }
}
