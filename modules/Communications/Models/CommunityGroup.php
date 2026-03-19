<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityGroup extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'community_groups';
    protected $fillable = ['community_id', 'group_id', 'is_announcement_channel'];

    protected function casts(): array
    {
        return ['is_announcement_channel' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
