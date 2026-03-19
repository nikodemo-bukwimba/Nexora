<?php

namespace Modules\Platform\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Modules\Platform\Contracts\Services\AuditLoggerInterface;
use Symfony\Component\Uid\Ulid;

class AuditLogger implements AuditLoggerInterface
{
    public function log(
        string $module,
        string $action,
        string $subjectType,
        string $subjectId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $actorId = null,
        ?array $metadata = null
    ): void {
        if (! $this->isEnabled($module, $action)) {
            return;
        }

        try {
            DB::connection('platform')->table('audit_logs')->insert([
                'id'           => (string) new Ulid(),
                'module'       => $module,
                'action'       => $action,
                'actor_id'     => $actorId,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'old_values'   => $oldValues  ? json_encode($oldValues)  : null,
                'new_values'   => $newValues  ? json_encode($newValues)  : null,
                'metadata'     => $metadata   ? json_encode($metadata)   : null,
                'ip_address'   => Request::ip(),
                'user_agent'   => Request::userAgent(),
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit failure must never crash the request
            Log::error("AuditLogger: failed to write audit entry [{$module}.{$action}]: " . $e->getMessage());
        }
    }

    public function isEnabled(string $module, string $action): bool
    {
        $config = DB::connection('platform')
            ->table('audit_log_configs')
            ->where('module', $module)
            ->where('action', $action)
            ->first();

        // If no config exists for this action, default to enabled
        return $config === null || (bool) $config->is_enabled;
    }
}
