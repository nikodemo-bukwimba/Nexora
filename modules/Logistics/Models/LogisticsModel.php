<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Logistics\Traits\HasUlid;

abstract class LogisticsModel extends Model
{
    use HasUlid;

    protected $connection = 'logistics';
    protected $keyType    = 'string';
    public $incrementing  = false;
}
