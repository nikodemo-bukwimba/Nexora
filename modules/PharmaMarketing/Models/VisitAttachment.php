<?php

namespace Modules\PharmaMarketing\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitAttachment extends PharmaModel
{
    public $timestamps  = false;
    protected $table    = 'pm_visit_attachments';
    protected $fillable = [
        'visit_id', 'uploaded_by', 'type', 'file_name', 'file_url',
        'mime_type', 'file_size_bytes', 'width', 'height',
        'caption', 'latitude', 'longitude', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(FieldVisit::class, 'visit_id');
    }

    public function isPhoto(): bool    { return $this->type === 'photo'; }
    public function isDocument(): bool { return $this->type === 'document'; }
}
