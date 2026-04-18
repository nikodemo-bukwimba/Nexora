<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\OrgRole;
use Modules\Platform\Models\Organization;
use Modules\Platform\Models\User;

/**
 * OfficerController
 *
 * Officers are OrgMembership records whose org_role has slug = 'officer'.
 * There is no separate Officer table — this controller is a filtered
 * view over the platform's org_memberships + users + actors.
 *
 * Routes (prefix: /api/v1/pharma):
 *   GET    orgs/{orgId}/officers                  → index
 *   GET    orgs/{orgId}/officers/{officerId}       → show   (officerId = membership id)
 *   POST   orgs/{orgId}/officers                  → store  (assign existing user as officer)
 *   PATCH  orgs/{orgId}/officers/{officerId}       → update (update membership level/status)
 *   DELETE orgs/{orgId}/officers/{officerId}       → destroy
 */
class OfficerController extends Controller
{
    /**
     * GET /api/v1/pharma/orgs/{orgId}/officers
     *
     * Lists all officer memberships for an org (root or branch).
     * For root orgs: includes officers from all branches in the tree.
     * Filterable by: branch_id, status
     */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $org = Organization::findOrFail($orgId);

        // Collect org IDs in scope
        if ($org->type === 'root') {
            $scopeOrgIds = Organization::where('root_org_id', $orgId)
                ->orWhere('id', $orgId)
                ->pluck('id');
        } else {
            $scopeOrgIds = collect([$orgId]);
        }

        $officers = OrgMembership::whereIn('org_id', $scopeOrgIds)
            ->whereHas('orgRole', fn($q) => $q->where('slug', 'officer'))
            ->with([
                'user.actor',
                'orgRole',
                'organization',
            ])
            ->when($request->branch_id, fn($q, $v) => $q->where('org_id', $v))
            ->when($request->status,    fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('joined_at')
            ->paginate((int) $request->get('per_page', 25));

        // Shape the response to look like officers, not raw memberships
        $officers->getCollection()->transform(fn($m) => self::formatOfficer($m));

        return response()->json($officers);
    }

    /**
     * GET /api/v1/pharma/orgs/{orgId}/officers/{officerId}
     *
     * officerId = the membership ID (ULID, 26 chars)
     */
    public function show(string $orgId, string $officerId): JsonResponse
    {
        $membership = OrgMembership::where('id', $officerId)
            ->where('org_id', $orgId)
            ->whereHas('orgRole', fn($q) => $q->where('slug', 'officer'))
            ->with(['user.actor', 'orgRole', 'organization'])
            ->firstOrFail();

        return response()->json(self::formatOfficer($membership));
    }

    /**
     * POST /api/v1/pharma/orgs/{orgId}/officers
     *
     * Assign an existing platform user as an officer in this org.
     * The user must already exist (registered via /auth/register).
     * If the user is already a root-org member, assigns directly (active).
     * If not yet a root-org member, creates an invited membership.
     *
     * Body:
     *   user_id   string  required  ULID of the platform user
     *   level     int     optional  0-99, default 10
     */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'string', 'size:26', 'exists:platform.users,id'],
            'level'   => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        $org   = Organization::findOrFail($orgId);
        $level = $request->level ?? 10;

        // Find the 'officer' role for this org tree
        $rootOrgId   = $org->root_org_id ?? $org->id;
        $officerRole = OrgRole::where('root_org_id', $rootOrgId)
            ->where('slug', 'officer')
            ->first();

        if (! $officerRole) {
            // Auto-create the officer role if it doesn't exist yet
            $officerRole = OrgRole::create([
                'root_org_id' => $rootOrgId,
                'name'        => 'Officer',
                'slug'        => 'officer',
                'source'      => 'custom',
                'is_system'   => false,
            ]);
        }

        // Prevent duplicate: check if user already has an officer membership here
        $existing = OrgMembership::where('user_id', $request->user_id)
            ->where('org_id', $orgId)
            ->first();

        if ($existing) {
            if ($existing->status === 'active') {
                return response()->json([
                    'message' => 'User is already an active officer in this branch.',
                ], 422);
            }

            // Reactivate if suspended
            $existing->update([
                'org_role_id' => $officerRole->id,
                'level'       => $level,
                'status'      => 'active',
                'joined_at'   => now(),
            ]);

            return response()->json([
                'message' => 'Officer reactivated.',
                'officer' => self::formatOfficer($existing->fresh(['user.actor', 'orgRole', 'organization'])),
            ]);
        }

        // Check root org membership to decide direct assign vs invite
        $rootMembership = OrgMembership::where('user_id', $request->user_id)
            ->where('org_id', $rootOrgId)
            ->where('status', 'active')
            ->first();

        $status   = $rootMembership ? 'active' : 'invited';
        $joinedAt = $rootMembership ? now() : null;

        $membership = OrgMembership::create([
            'user_id'     => $request->user_id,
            'org_id'      => $orgId,
            'org_role_id' => $officerRole->id,
            'level'       => $level,
            'invited_by'  => $request->user()->id,
            'status'      => $status,
            'joined_at'   => $joinedAt,
        ]);

        return response()->json([
            'message' => $rootMembership ? 'Officer assigned.' : 'Officer invited (pending acceptance).',
            'officer' => self::formatOfficer($membership->fresh(['user.actor', 'orgRole', 'organization'])),
        ], 201);
    }

    /**
     * PATCH /api/v1/pharma/orgs/{orgId}/officers/{officerId}
     *
     * Update an officer's level or status.
     *
     * Body (all optional):
     *   level   int     0-99
     *   status  string  active|suspended
     */
    public function update(Request $request, string $orgId, string $officerId): JsonResponse
    {
        $request->validate([
            'level'  => ['sometimes', 'integer', 'min:0', 'max:99'],
            'status' => ['sometimes', 'string', 'in:active,suspended'],
        ]);

        $membership = OrgMembership::where('id', $officerId)
            ->where('org_id', $orgId)
            ->whereHas('orgRole', fn($q) => $q->where('slug', 'officer'))
            ->firstOrFail();

        $membership->update(array_filter(
            $request->only(['level', 'status']),
            fn($v) => ! is_null($v)
        ));

        return response()->json([
            'message' => 'Officer updated.',
            'officer' => self::formatOfficer($membership->fresh(['user.actor', 'orgRole', 'organization'])),
        ]);
    }

    /**
     * DELETE /api/v1/pharma/orgs/{orgId}/officers/{officerId}
     *
     * Removes (suspends) an officer from this branch.
     * Does NOT delete the platform user.
     */
    public function destroy(string $orgId, string $officerId): JsonResponse
    {
        $membership = OrgMembership::where('id', $officerId)
            ->where('org_id', $orgId)
            ->whereHas('orgRole', fn($q) => $q->where('slug', 'officer'))
            ->firstOrFail();

        $membership->update(['status' => 'suspended']);

        return response()->json(['message' => 'Officer removed from branch.']);
    }

    /**
     * Shape a membership record into the officer response format.
     */
    private static function formatOfficer(OrgMembership $membership): array
    {
        $user  = $membership->user;
        $actor = $user?->actor;

        return [
            'id'           => $membership->id,       // membership ID — use for show/update/delete
            'user_id'      => $membership->user_id,
            'actor_id'     => $user?->actor_id,
            'name'         => $actor?->display_name ?? $user?->username ?? 'Unknown',
            'username'     => $user?->username,
            'email'        => $user?->email,
            'status'       => $membership->status,
            'level'        => $membership->level,
            'joined_at'    => $membership->joined_at?->toISOString(),
            'role'         => [
                'id'   => $membership->orgRole?->id,
                'name' => $membership->orgRole?->name,
                'slug' => $membership->orgRole?->slug,
            ],
            'branch'       => [
                'id'   => $membership->organization?->id,
                'name' => $membership->organization?->name,
                'type' => $membership->organization?->type,
            ],
            'avatar_url'   => $actor?->avatar_url,
            'actor_status' => $actor?->status,
        ];
    }
}