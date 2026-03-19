<?php

namespace Modules\Platform\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Platform\Models\Organization;
use Modules\Platform\Models\User;

interface PlatformAdminServiceInterface
{
    // ── Staff ──────────────────────────────────────────────────
    public function assignStaffRole(string $userId, string $roleName, string $grantedBy): void;
    public function revokeStaffRole(string $userId, string $roleName): void;
    public function listStaff(array $filters, int $perPage): LengthAwarePaginator;

    // ── Organizations ──────────────────────────────────────────
    public function listOrganizations(array $filters, int $perPage): LengthAwarePaginator;
    public function getOrganization(string $id): Organization;
    public function approveOrganization(string $id, string $approvedBy): Organization;
    public function rejectOrganization(string $id, string $rejectedBy, string $reason): Organization;
    public function suspendOrganization(string $id, string $suspendedBy): Organization;
    public function reactivateOrganization(string $id, string $reactivatedBy): Organization;

    // ── Users ──────────────────────────────────────────────────
    public function listUsers(array $filters, int $perPage): LengthAwarePaginator;
    public function updateUserStatus(string $userId, string $status): User;
    public function assignUserTier(string $userId, string $tierName, string $assignedBy): void;

    // ── Feature Flags ──────────────────────────────────────────
    public function listFlags(): array;
    public function toggleFlag(string $key, bool $value, string $updatedBy): object;

    // ── Tiers ──────────────────────────────────────────────────
    public function listTiers(): array;

    // ── Audit ──────────────────────────────────────────────────
    public function queryAuditLog(array $filters, int $perPage): LengthAwarePaginator;
}
