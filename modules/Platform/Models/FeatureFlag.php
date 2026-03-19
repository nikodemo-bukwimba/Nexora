<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Platform\Traits\HasUlid;

class FeatureFlag extends PlatformModel
{
    use HasUlid;

    protected $table    = 'platform_feature_flags';
    protected $fillable = ['key', 'value', 'description', 'module', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'boolean'];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
