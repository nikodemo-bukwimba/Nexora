<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Models\PmOfficer;
use Modules\PharmaMarketing\Services\OfficerService;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Models\OrgMembership;
use Modules\Platform\Models\User;
use Modules\Platform\Models\Actor;

class OfficerController extends Controller
{
    public function __construct(protected OfficerService $officerService) {}

    /**
     * GET /api/v1/pharma/orgs/{orgId}/officers
     *
     * Lists pm_officers for the given org/branch.
     * Enriches each officer with membership and user details.
     */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $officers = PmOfficer::where('org_id', $orgId)
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name', 'ilike', "%{$v}%")
                  ->orWhere('email', 'ilike', "%{$v}%");
            }))
            ->orderBy('name')
            ->paginate((int) $request->get('per_page', 25));

        // Enrich with membership and user data for the admin app
        $officers->getCollection()->transform(fn($o) => $this->format($o, $orgId));

        return response()->json($officers);
    }

    /**
     * GET /api/v1/pharma/orgs/{orgId}/officers/{officerId}
     *
     * Returns a single officer by their pm_officers.id OR actor_id.
     * Supports both ID types so the admin app can fetch by either.
     */
    public function show(string $orgId, string $officerId): JsonResponse
    {
        $officer = PmOfficer::where('org_id', $orgId)
            ->where(function ($q) use ($officerId) {
                $q->where('id', $officerId)
                  ->orWhere('actor_id', $officerId)
                  ->orWhere('platform_user_id', $officerId);
            })
            ->firstOrFail();

        return response()->json(['officer' => $this->format($officer, $orgId)]);
    }

    /**
     * POST /api/v1/pharma/orgs/{orgId}/officers
     *
     * Creates a platform user + org membership + pm_officer record atomically.
     * This is the "Create Officer" flow from the admin app.
     *
     * Request body:
     *   email            required
     *   username         required (used as display name too)
     *   phone            optional
     *   org_role_id      required  — platform org_roles.id for this branch
     *   level            optional  default 40
     *   app_password     optional  — if provided, creates platform login immediately
     *   app_password_confirmation — required if app_password set
     */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'email'                    => ['required', 'string', 'email'],
            'username'                 => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/'],
            'phone'                    => ['nullable', 'string'],
            'org_role_id'              => ['required', 'string', 'size:26'],
            'level'                    => ['nullable', 'integer', 'min:0', 'max:100'],
            'app_password'             => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $authService = app(AuthServiceInterface::class);
        $level = $request->input('level', 40);

        return DB::connection('platform')->transaction(function () use ($request, $orgId, $authService, $level) {

            // 1. Derive root org id for pm_officers.org_id
            $branch = \Modules\Platform\Models\Organization::findOrFail($orgId);
            $rootOrgId = $branch->root_org_id ?? $orgId;

            // 2. Create or find platform user
            $existingUser = User::where('email', $request->email)->first();

            if ($existingUser) {
                $user = $existingUser;
            } else {
                $username = $request->username
                    ?? preg_replace('/[^a-zA-Z0-9_]/', '_', explode('@', $request->email)[0])
                    . '_' . substr(uniqid(), -4);

                // If password provided, register immediately with login
                $password = $request->filled('app_password')
                    ? $request->app_password
                    : \Illuminate\Support\Str::random(24); // random non-usable password

                $user = $authService->register([
                    'name'     => $request->input('username', explode('@', $request->email)[0]),
                    'username' => $username,
                    'email'    => $request->email,
                    'password' => $password,
                ]);
            }

            // 3. Create org membership at the branch level
            $existingMembership = OrgMembership::where('user_id', $user->id)
                ->where('org_id', $orgId)
                ->where('status', 'active')
                ->first();

            if (!$existingMembership) {
                OrgMembership::create([
                    'user_id'     => $user->id,
                    'org_id'      => $orgId,
                    'org_role_id' => $request->org_role_id,
                    'level'       => $level,
                    'invited_by'  => $request->user()->id,
                    'status'      => 'active',
                    'joined_at'   => now(),
                ]);
            }

            // 4. Create pm_officer record
            $officer = $this->officerService->createFromAdminOrg(
                orgId:          $rootOrgId,
                branchId:       $orgId,
                platformUserId: $user->id,
                actorId:        $user->actor_id,
                name:           $request->input('username', $user->username),
                email:          $user->email,
                phone:          $request->phone,
                source:         'admin',
            );

            return response()->json([
                'message' => 'Officer created successfully.',
                'officer' => $this->format($officer, $orgId),
            ], 201);
        });
    }

    /**
     * PATCH /api/v1/pharma/orgs/{orgId}/officers/{officerId}
     *
     * Updates officer name, phone, status in pm_officers.
     * Also updates org membership role/level.
     */
    public function update(Request $request, string $orgId, string $officerId): JsonResponse
    {
        $request->validate([
            'name'        => ['sometimes', 'string', 'min:2'],
            'phone'       => ['sometimes', 'nullable', 'string'],
            'status'      => ['sometimes', 'string', 'in:active,suspended'],
            'org_role_id' => ['sometimes', 'string', 'size:26'],
            'level'       => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        $officer = PmOfficer::where('org_id', $orgId)
            ->where(function ($q) use ($officerId) {
                $q->where('id', $officerId)
                  ->orWhere('actor_id', $officerId);
            })
            ->firstOrFail();

        // Update pm_officer fields
        $officer->update(array_filter([
            'name'   => $request->name,
            'phone'  => $request->phone,
            'status' => $request->status,
        ], fn($v) => !is_null($v)));

        // Update org membership if role/level changed
        if ($request->filled('org_role_id') || $request->filled('level') || $request->filled('status')) {
            OrgMembership::where('user_id', $officer->platform_user_id)
                ->where('org_id', $orgId)
                ->update(array_filter([
                    'org_role_id' => $request->org_role_id,
                    'level'       => $request->level,
                    'status'      => $request->status,
                ], fn($v) => !is_null($v)));
        }

        return response()->json([
            'message' => 'Officer updated.',
            'officer' => $this->format($officer->fresh(), $orgId),
        ]);
    }

    /**
     * DELETE /api/v1/pharma/orgs/{orgId}/officers/{officerId}
     *
     * Soft-deletes pm_officer and suspends org membership.
     */
    public function destroy(string $orgId, string $officerId): JsonResponse
    {
        $officer = PmOfficer::where('org_id', $orgId)
            ->where(function ($q) use ($officerId) {
                $q->where('id', $officerId)
                  ->orWhere('actor_id', $officerId);
            })
            ->firstOrFail();

        // Suspend org membership instead of hard delete
        OrgMembership::where('user_id', $officer->platform_user_id)
            ->where('org_id', $orgId)
            ->update(['status' => 'suspended']);

        $officer->update(['status' => 'suspended']);
        $officer->delete();

        return response()->json(['message' => 'Officer removed.']);
    }

    // ── Private helper ──────────────────────────────────────

    /**
     * Enrich a PmOfficer record with membership and user details.
     * Returns a shape that OfficerModel.fromJson() on the admin app can parse.
     */
    private function format(PmOfficer $officer, string $orgId): array
    {
        $data = $officer->toArray();

        // Resolve user and membership details
        $user = $officer->platform_user_id
            ? User::find($officer->platform_user_id)
            : null;

        $actor = $officer->actor_id
            ? Actor::find($officer->actor_id)
            : null;

        $membership = $user
            ? OrgMembership::where('user_id', $user->id)
                ->where('org_id', $orgId)
                ->where('status', 'active')
                ->with('orgRole')
                ->first()
            : null;

        // Build the shape OfficerModel.fromJson expects:
        // { user_id, actor_id, user: { id, username, email, phone, status, actor_id },
        //   role: { id, name }, org_id, org_role_id, level, status, created_at }
        $data['user_id']     = $user?->id ?? $officer->platform_user_id;
        $data['actor_id']    = $actor?->id ?? $officer->actor_id;
        $data['user']        = [
            'id'       => $user?->id,
            'username' => $user?->username ?? $officer->name,
            'email'    => $user?->email ?? $officer->email,
            'phone'    => $officer->phone,
            'status'   => $user?->status ?? 'active',
            'actor_id' => $actor?->id ?? $officer->actor_id,
        ];
        $data['role']        = $membership ? [
            'id'   => $membership->org_role_id,
            'name' => $membership->orgRole?->name,
        ] : ['id' => null, 'name' => null];
        $data['org_role_id'] = $membership?->org_role_id;
        $data['level']       = $membership?->level ?? 0;
        $data['status']      = $membership?->status ?? $officer->status;
        $data['org_name']    = null; // filled if needed

        return $data;
    }
}