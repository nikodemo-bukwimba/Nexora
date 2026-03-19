<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends CommerceModel
{
    use SoftDeletes;

    protected $fillable = [
        'org_id', 'seller_actor_id', 'name', 'slug', 'description',
        'type', 'status', 'requires_confirmation', 'track_inventory',
        'media', 'attributes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requires_confirmation' => 'boolean',
            'track_inventory'       => 'boolean',
            'media'                 => 'array',
            'attributes'            => 'array',
            'metadata'              => 'array',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->orderBy('sort_order');
    }

    public function defaultVariant()
    {
        return $this->variants()->where('is_default', true)->where('is_active', true)->first()
            ?? $this->variants()->where('is_active', true)->first();
    }

    public function bundleComponents(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'bundle_product_id');
    }

    public function isActive(): bool   { return $this->status === 'active'; }
    public function isPhysical(): bool { return $this->type === 'physical'; }
    public function isBundle(): bool   { return $this->type === 'bundle'; }
    public function isService(): bool  { return $this->type === 'service'; }
    public function isDigital(): bool  { return $this->type === 'digital'; }
}
