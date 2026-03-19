<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgScopeGrantBranch extends PlatformModel
{
    public $incrementing  = false;
    public $timestamps    = false;
    protected $primaryKey = null;
    protected $fillable   = ['scope_grant_id', 'org_id'];

    public function scopeGrant(): BelongsTo
    {
        return $this->belongsTo(OrgScopeGrant::class);
    }

    public function org(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
