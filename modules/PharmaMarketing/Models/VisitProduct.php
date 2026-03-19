<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitProduct extends PharmaModel
{
    public $timestamps  = false;
    protected $table    = 'pm_visit_products';
    protected $fillable = [
        'visit_id', 'product_id', 'product_name',
        'action', 'samples_given', 'customer_feedback', 'notes',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(FieldVisit::class, 'visit_id');
    }
}
