<?php

namespace Modules\Platform\Contracts\Services;

interface AuditLoggerInterface
{
    /**
     * Write an audit log entry.
     *
     * @param string      $module       e.g. 'platform', 'finance'
     * @param string      $action       e.g. 'org.approved', 'user.suspended'
     * @param string      $subjectType  e.g. 'Organization', 'User'
     * @param string      $subjectId    ULID of the subject record
     * @param array|null  $oldValues    State before the change
     * @param array|null  $newValues    State after the change
     * @param string|null $actorId      Actor who performed the action (null = system)
     * @param array|null  $metadata     Any additional context
     */
    public function log(
        string $module,
        string $action,
        string $subjectType,
        string $subjectId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $actorId = null,
        ?array $metadata = null
    ): void;

    /**
     * Check if a module+action combination is enabled for auditing.
     */
    public function isEnabled(string $module, string $action): bool;
}
