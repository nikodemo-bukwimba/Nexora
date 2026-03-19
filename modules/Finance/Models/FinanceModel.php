<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Traits\HasUlid;

/**
 * Base model for all Finance module models.
 * Uses the 'finance' connection which maps to the finance PostgreSQL schema.
 */
abstract class FinanceModel extends Model
{
    use HasUlid;

    protected $connection = 'finance';
    protected $keyType    = 'string';
    public $incrementing  = false;
}
