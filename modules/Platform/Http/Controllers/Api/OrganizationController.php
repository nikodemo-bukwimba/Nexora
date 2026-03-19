<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgs
    ) {}

    /** POST /api/v1/orgs */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'min:2', 'max:255'],
            'slug'     => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:platform.organizations,slug'],
            'settings' => ['nullable', 'array'],
        ]);

        $org = $this->orgs->createRootOrg($request->only(['name', 'slug', 'settings']), $request->user()->id);

        return response()->json([
            'message' => 'Organization created. Awaiting platform admin approval.',
            'org'     => $org->load('actor'),
        ], 201);
    }

    /** GET /api/v1/orgs/{slug} */
    public function show(string $slug): JsonResponse
    {
        $org = $this->orgs->getBySlug($slug);
        return response()->json($org->load('actor', 'parent'));
    }

    /** PATCH /api/v1/orgs/{slug} */
    public function update(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'name'     => ['sometimes', 'string', 'min:2', 'max:255'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ]);

        $org = $this->orgs->getBySlug($slug);
        $updated = $this->orgs->update($org->id, $request->only(['name', 'settings']), $request->user()->id);

        return response()->json($updated->load('actor'));
    }

    /** GET /api/v1/orgs/{slug}/tree */
    public function tree(string $slug): JsonResponse
    {
        $org  = $this->orgs->getBySlug($slug);
        $tree = $this->orgs->getTree($org->root_org_id ?? $org->id);
        return response()->json(['tree' => $tree]);
    }

    /** POST /api/v1/orgs/{slug}/branches */
    public function storeBranch(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'min:2', 'max:255'],
            'slug'     => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:platform.organizations,slug'],
            'settings' => ['nullable', 'array'],
        ]);

        $parent = $this->orgs->getBySlug($slug);
        $branch = $this->orgs->createBranch(
            $request->only(['name', 'slug', 'settings']),
            $parent->id,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Branch created.',
            'org'     => $branch->load('actor', 'parent'),
        ], 201);
    }

    /** GET /api/v1/orgs/{slug}/ancestors */
    public function ancestors(string $slug): JsonResponse
    {
        $org       = $this->orgs->getBySlug($slug);
        $ancestors = $this->orgs->getAncestors($org->id);
        return response()->json($ancestors);
    }

    /** GET /api/v1/orgs/{slug}/descendants */
    public function descendants(string $slug): JsonResponse
    {
        $org         = $this->orgs->getBySlug($slug);
        $descendants = $this->orgs->getDescendants($org->id);
        return response()->json($descendants);
    }
}
