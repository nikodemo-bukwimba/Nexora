<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\PharmaMarketing\Traits\HasUlid;

abstract class PharmaModel extends Model
{
    use HasUlid;

    protected $connection = 'pharma_marketing';
    protected $keyType    = 'string';
    public $incrementing  = false;
}
