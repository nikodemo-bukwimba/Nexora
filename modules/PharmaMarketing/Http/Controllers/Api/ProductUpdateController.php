<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PharmaMarketing\Services\ProductUpdateService;

class ProductUpdateController extends Controller
{
    public function __construct(protected ProductUpdateService $updates) {}

    /** GET /api/v1/pharma/orgs/{orgId}/product-updates */
    public function index(Request $request, string $orgId): JsonResponse
    {
        return response()->json($this->updates->list($orgId, $request->only(['status', 'update_type']), (int) $request->get('per_page', 25)));
    }

    /** POST /api/v1/pharma/orgs/{orgId}/product-updates */
    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'title'  => ['required', 'string', 'max:255'],
            'body'   => ['required', 'string'],
        ]);
        $update = $this->updates->create($orgId, $request->user()->actor_id, $request->all());
        return response()->json(['message' => 'Product update created.', 'update' => $update], 201);
    }

    /** GET /api/v1/pharma/product-updates/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->updates->get($id));
    }
        /** PATCH /api/v1/pharma/product-updates/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'title'   => ['sometimes', 'string', 'max:255'],
            'body'    => ['sometimes', 'string'],
            'status'  => ['sometimes', 'string', 'in:draft,sending,sent,failed'],
        ]);
        $update = $this->updates->patch($id, $request->all());
        return response()->json(['message' => 'Product update updated.', 'update' => $update]);
    }

    /** POST /api/v1/pharma/product-updates/{id}/publish */
    public function publish(string $id): JsonResponse
    {
        $update = $this->updates->publish($id);
        return response()->json(['message' => 'Product update sent to customers.', 'update' => $update]);
    }

    /** GET /api/v1/pharma/product-updates/{id}/stats */
    public function stats(string $id): JsonResponse
    {
        return response()->json($this->updates->getDeliveryStats($id));
    }
}
