<?php
namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PmOfficer extends PharmaModel
{
    use SoftDeletes;

    protected $table = 'pm_officers';
    protected $fillable = [
        'org_id', 'branch_id', 'platform_user_id', 'actor_id',
        'registration_source', 'name', 'email', 'phone', 'status', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function isSelfRegistered(): bool
    {
        return $this->registration_source === 'self_registered';
    }
}