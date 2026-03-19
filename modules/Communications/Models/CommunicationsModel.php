<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Communications\Traits\HasUlid;

abstract class CommunicationsModel extends Model
{
    use HasUlid;

    protected $connection = 'communications';
    protected $keyType    = 'string';
    public $incrementing  = false;
}
