<?php

namespace Modules\Communications\Models;

class MessageAttachment extends CommunicationsModel
{
    public $timestamps  = false;
    protected $table    = 'message_attachments';
    protected $fillable = [
        'id',                  // ← added so create(['id' => ...]) works if ever passed
        'message_type', 'message_id', 'type', 'file_name', 'file_url',
        'mime_type', 'file_size_bytes', 'duration_seconds',
        'width', 'height', 'thumbnail_url', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }
}