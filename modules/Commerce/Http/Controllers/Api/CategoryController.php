<?php
namespace Modules\Commerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commerce\Models\Category;

class CategoryController extends Controller {
    /** GET /api/v1/commerce/orgs/{orgId}/categories */
    public function index(Request $request, string $orgId): JsonResponse {
        $categories = Category::where('org_id', $orgId)
            ->when($request->search, fn($q, $v) => $q->where('name', 'ilike', "%$v%"))
            ->orderBy('name')
            ->paginate((int) $request->get('per_page', 25));
        return response()->json($categories);
    }

    /** POST /api/v1/commerce/orgs/{orgId}/categories */
    public function store(Request $request, string $orgId): JsonResponse {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);
        $category = Category::create(['org_id' => $orgId, ...$data]);
        return response()->json(['category' => $category], 201);
    }

    /** GET /api/v1/commerce/categories/{id} */
    public function show(string $id): JsonResponse {
        return response()->json(['category' => Category::findOrFail($id)]);
    }

    /** PATCH /api/v1/commerce/categories/{id} */
    public function update(Request $request, string $id): JsonResponse {
        $category = Category::findOrFail($id);
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);
        $category->update($data);
        return response()->json(['category' => $category->fresh()]);
    }

    /** DELETE /api/v1/commerce/categories/{id} */
    public function destroy(string $id): JsonResponse {
        Category::findOrFail($id)->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}