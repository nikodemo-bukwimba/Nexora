<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Notifications\Traits\HasUlid;

abstract class NotificationsModel extends Model
{
    use HasUlid;

    protected $connection = 'notifications';
    protected $keyType    = 'string';
    public $incrementing  = false;
}
