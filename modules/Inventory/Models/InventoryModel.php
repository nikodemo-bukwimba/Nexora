<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Traits\HasUlid;

abstract class InventoryModel extends Model
{
    use HasUlid;

    protected $connection = 'inventory';
    protected $keyType    = 'string';
    public $incrementing  = false;
}
