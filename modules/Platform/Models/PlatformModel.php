<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Platform\Traits\HasUlid;

abstract class PlatformModel extends Model
{
    use HasUlid;

    /**
     * All Platform models use the 'platform' connection
     * which maps to the platform schema in PostgreSQL.
     */
    protected $connection = 'platform';

    /**
     * ULID primary keys are strings, not integers.
     */
    protected $keyType = 'string';

    public $incrementing = false;
}
