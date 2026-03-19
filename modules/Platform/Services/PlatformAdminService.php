<?php

namespace Modules\Platform\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Contracts\Services\AuditLoggerInterface;
use Modules\Platform\Contracts\Services\EventBusInterface;
use Modules\Platform\Contracts\Services\PlatformAdminServiceInterface;
use Modules\Platform\Events\OrgApproved;
use Modules\Platform\Events\OrgRejected;
use Modules\Platform\Events\OrgSuspended;
use Modules\Platform\Models\Organization;
use Modules\Platform\Models\PlatformTier;
use Modules\Platform\Models\User;
use Modules\Platform\Models\UserTierAssignment;

class PlatformAdminService implements PlatformAdminServiceInterface
{
    public function __construct(
        protected EventBusInterface    $eventBus,
        protected AuditLoggerInterface $audit,
    ) {}

    // ── Staff ──────────────────────────────────────────────────

    public function assignStaffRole(string $userId, string $roleName, string $grantedBy): void
    {
        $role = DB::connection('platform')->table('platform_roles')->where('name', $roleName)->firstOrFail();

        DB::connection('platform')->table('user_platform_roles')->insertOrIgnore([
            'user_id'          => $userId,
            'platform_role_id' => $role->id,
            'granted_by'       => $grantedBy,
            'granted_at'       => now(),
        ]);

        $grantedByUser = User::find($grantedBy);
        $this->audit->log('platform', 'staff.role.assigned', 'User', $userId,
            null, ['role' => $roleName], $grantedByUser?->actor_id);
    }

    public function revokeStaffRole(string $userId, string $roleName): void
    {
        $role = DB::connection('platform')->table('platform_roles')->where('name', $roleName)->firstOrFail();

        DB::connection('platform')->table('user_platform_roles')
            ->where('user_id', $userId)
            ->where('platform_role_id', $role->id)
            ->delete();
    }

    public function listStaff(array $filters, int $perPage): LengthAwarePaginator
    {
        return User::whereHas('platformRoles')
            ->with(['actor', 'platformRoles.platformRole'])
            ->paginate($perPage);
    }

    // ── Organizations ──────────────────────────────────────────

    public function listOrganizations(array $filters, int $perPage): LengthAwarePaginator
    {
        return Organization::with('actor')
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), fn($q) => $q->where('name', 'ilike', "%{$filters['search']}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getOrganization(string $id): Organization
    {
        return Organization::with(['actor'])->findOrFail($id);
    }

    public function approveOrganization(string $id, string $approvedBy): Organization
    {
        $org = Organization::findOrFail($id);

        if ($org->status !== 'pending_approval') {
            throw new \RuntimeException("Organization is not pending approval. Current status: {$org->status}");
        }

        $old = $org->only(['status', 'approved_by', 'approved_at']);

        $org->forceFill([
            'status'           => 'active',
            'approved_by'      => $approvedBy,
            'approved_at'      => now(),
            'rejection_reason' => null,
        ])->save();

        $org->refresh();

        $approver = User::find($approvedBy);
        $this->eventBus->fire(new OrgApproved($org, $approvedBy), $approver?->actor_id);
        $this->audit->log('platform', 'org.approved', 'Organization', $id,
            $old, $org->only(['status', 'approved_by', 'approved_at']), $approver?->actor_id);

        return $org->load('actor');
    }

    public function rejectOrganization(string $id, string $rejectedBy, string $reason): Organization
    {
        $org = Organization::findOrFail($id);

        if ($org->status !== 'pending_approval') {
            throw new \RuntimeException("Organization is not pending approval. Current status: {$org->status}");
        }

        $old = $org->only(['status']);

        $org->forceFill([
            'status'           => 'rejected',
            'approved_by'      => $rejectedBy,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ])->save();

        $org->refresh();

        $rejector = User::find($rejectedBy);
        $this->eventBus->fire(new OrgRejected($org, $rejectedBy, $reason), $rejector?->actor_id);
        $this->audit->log('platform', 'org.rejected', 'Organization', $id,
            $old, ['status' => 'rejected', 'reason' => $reason], $rejector?->actor_id);

        return $org->load('actor');
    }

    public function suspendOrganization(string $id, string $suspendedBy): Organization
    {
        $org = Organization::findOrFail($id);

        if ($org->status !== 'active') {
            throw new \RuntimeException('Only active organizations can be suspended.');
        }

        $org->forceFill(['status' => 'suspended'])->save();
        $org->refresh();

        $suspender = User::find($suspendedBy);
        $this->eventBus->fire(new OrgSuspended($org, $suspendedBy), $suspender?->actor_id);
        $this->audit->log('platform', 'org.suspended', 'Organization', $id,
            ['status' => 'active'], ['status' => 'suspended'], $suspender?->actor_id);

        return $org->load('actor');
    }

    public function reactivateOrganization(string $id, string $reactivatedBy): Organization
    {
        $org = Organization::findOrFail($id);

        if ($org->status !== 'suspended') {
            throw new \RuntimeException('Only suspended organizations can be reactivated.');
        }

        $org->forceFill(['status' => 'active'])->save();
        $org->refresh();

        $this->audit->log('platform', 'org.reactivated', 'Organization', $id,
            ['status' => 'suspended'], ['status' => 'active']);

        return $org->load('actor');
    }

    // ── Users ──────────────────────────────────────────────────

    public function listUsers(array $filters, int $perPage): LengthAwarePaginator
    {
        return User::with('actor')
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), fn($q) =>
                $q->where('email', 'ilike', "%{$filters['search']}%")
                  ->orWhere('username', 'ilike', "%{$filters['search']}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function updateUserStatus(string $userId, string $status): User
    {
        $allowed = ['active', 'suspended', 'banned'];
        if (! in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Invalid status. Allowed: " . implode(', ', $allowed));
        }

        $user = User::findOrFail($userId);
        $old  = ['status' => $user->status];
        $user->forceFill(['status' => $status])->save();

        $this->audit->log('platform', 'user.status.updated', 'User', $userId,
            $old, ['status' => $status]);

        return $user->fresh(['actor']);
    }

    public function assignUserTier(string $userId, string $tierName, string $assignedBy): void
    {
        $tier = PlatformTier::where('name', $tierName)->where('is_active', true)->firstOrFail();

        UserTierAssignment::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'superseded', 'expires_at' => now()]);

        UserTierAssignment::create([
            'user_id'     => $userId,
            'tier_id'     => $tier->id,
            'assigned_by' => $assignedBy,
            'status'      => 'active',
        ]);

        $assigner = User::find($assignedBy);
        $this->audit->log('platform', 'user.tier.assigned', 'User', $userId,
            null, ['tier' => $tierName], $assigner?->actor_id);
    }

    // ── Feature Flags ──────────────────────────────────────────

    public function listFlags(): array
    {
        return DB::connection('platform')->table('platform_feature_flags')
            ->orderBy('module')->orderBy('key')->get()->toArray();
    }

    public function toggleFlag(string $key, bool $value, string $updatedBy): object
    {
        $flag = DB::connection('platform')->table('platform_feature_flags')->where('key', $key)->first();
        if (! $flag) throw new \RuntimeException("Feature flag '{$key}' not found.");

        DB::connection('platform')->table('platform_feature_flags')->where('key', $key)->update([
            'value'      => $value,
            'updated_by' => $updatedBy,
            'updated_at' => now(),
        ]);

        $updater = User::find($updatedBy);
        $this->audit->log('platform', 'flag.toggled', 'FeatureFlag', $key,
            ['value' => $flag->value], ['value' => $value], $updater?->actor_id);

        return DB::connection('platform')->table('platform_feature_flags')->where('key', $key)->first();
    }

    // ── Tiers ──────────────────────────────────────────────────

    public function listTiers(): array
    {
        return PlatformTier::orderBy('sort_order')->get()->toArray();
    }

    // ── Audit ──────────────────────────────────────────────────

    public function queryAuditLog(array $filters, int $perPage): LengthAwarePaginator
    {
        return \Modules\Platform\Models\AuditLog::query()
            ->when(isset($filters['module']),       fn($q) => $q->where('module', $filters['module']))
            ->when(isset($filters['action']),       fn($q) => $q->where('action', $filters['action']))
            ->when(isset($filters['actor_id']),     fn($q) => $q->where('actor_id', $filters['actor_id']))
            ->when(isset($filters['subject_type']), fn($q) => $q->where('subject_type', $filters['subject_type']))
            ->when(isset($filters['subject_id']),   fn($q) => $q->where('subject_id', $filters['subject_id']))
            ->when(isset($filters['from']),         fn($q) => $q->where('created_at', '>=', $filters['from']))
            ->when(isset($filters['to']),           fn($q) => $q->where('created_at', '<=', $filters['to']))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
