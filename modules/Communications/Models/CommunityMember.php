<?php

namespace Modules\Communications\Models;

class CommunityMember extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'community_members';
    protected $fillable = ['community_id', 'actor_id', 'role', 'status'];
}
