<?php

namespace Modules\Platform\Http\Controllers\Api\Org;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\OrganizationServiceInterface;
use Modules\Platform\Http\Requests\Org\CreateBranchRequest;
use Modules\Platform\Http\Requests\Org\CreateRootOrgRequest;
use Modules\Platform\Http\Requests\Org\UpdateOrgRequest;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationServiceInterface $orgService
    ) {}

    /** POST /api/v1/orgs */
    public function createRoot(CreateRootOrgRequest $request): JsonResponse
    {
        $org = $this->orgService->createRootOrg(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Organization created and pending approval.',
            'org'     => $org,
        ], 201);
    }

    /** POST /api/v1/orgs/{orgId}/branches */
    public function createBranch(CreateBranchRequest $request, string $orgId): JsonResponse
    {
        $branch = $this->orgService->createBranch(
            $request->validated(),
            $orgId,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Branch created.',
            'org'     => $branch,
        ], 201);
    }

    /** GET /api/v1/orgs/{id} */
    public function show(string $id): JsonResponse
    {
        $org = $this->orgService->getOrg($id);
        return response()->json($org);
    }

    /** GET /api/v1/orgs/{id}/tree */
    public function tree(string $id): JsonResponse
    {
        $tree = $this->orgService->getOrgTree($id);
        return response()->json($tree);
    }

    /** PATCH /api/v1/orgs/{id} */
    public function update(UpdateOrgRequest $request, string $id): JsonResponse
    {
        $org = $this->orgService->updateOrg($id, $request->validated(), $request->user()->id);
        return response()->json(['message' => 'Organization updated.', 'org' => $org]);
    }
}
