<?php
namespace Modules\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Platform\Traits\HasUlid;

class Category extends Model {
    use HasUlid, SoftDeletes;

    protected $connection  = 'commerce';
    protected $table       = 'categories';
    protected $keyType     = 'string';
    public    $incrementing = false;

    protected $fillable = ['org_id', 'name', 'description', 'is_active'];

    protected function casts(): array {
        return ['is_active' => 'boolean'];
    }
}