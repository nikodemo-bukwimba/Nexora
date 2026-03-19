<?php

namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Commerce\Traits\HasUlid;

abstract class CommerceModel extends Model
{
    use HasUlid;

    protected $connection = 'commerce';
    protected $keyType    = 'string';
    public $incrementing  = false;
}
