<?php

namespace Modules\Notifications\Models;

class Notification extends NotificationsModel
{
    public $timestamps  = false;
    protected $fillable = [
        'actor_id', 'type', 'title', 'body', 'channel',
        'action_url', 'ref_type', 'ref_id', 'data',
        'status', 'sent_at', 'delivered_at', 'read_at',
        'retry_count', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'data'         => 'array',
            'sent_at'      => 'datetime',
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
            'created_at'   => 'datetime',
        ];
    }

    public function isRead(): bool    { return $this->status === 'read'; }
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isFailed(): bool  { return $this->status === 'failed'; }
}
