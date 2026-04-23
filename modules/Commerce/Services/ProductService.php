<?php

namespace Modules\Commerce\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\Commerce\Models\Product;
use Modules\Commerce\Models\ProductAttribute;
use Modules\Commerce\Models\ProductBundle;
use Modules\Commerce\Models\ProductVariant;

class ProductService
{
    public function __construct(
        protected OrgScopeResolverInterface $scope
    ) {}

    public function create(string $orgId, string $sellerActorId, array $data): Product
    {
        return DB::connection('commerce')->transaction(function () use ($orgId, $sellerActorId, $data) {
            $variants = $data['variants'] ?? [];
            unset($data['variants']);

            // Products are always owned by the ROOT org — never a branch.
            // This ensures the catalog is shared across all branches.
            $rootOrgId = $this->scope->rootId($orgId);

            $product = Product::create(array_merge($data, [
                'org_id'          => $rootOrgId,
                'seller_actor_id' => $sellerActorId,
                'slug'            => $this->generateSlug($rootOrgId, $data['name']),
                'status'          => 'draft',
            ]));

            foreach ($variants as $i => $variantData) {
                $attributes = $variantData['attributes'] ?? [];
                unset($variantData['attributes']);

                $variant = ProductVariant::create(array_merge($variantData, [
                    'product_id' => $product->id,
                    'is_default' => $i === 0,
                    'sort_order' => $i,
                ]));

                foreach ($attributes as $key => $value) {
                    ProductAttribute::create([
                        'variant_id' => $variant->id,
                        'key'        => $key,
                        'value'      => $value,
                    ]);
                }
            }

            return $product->fresh(['variants.attributes']);
        });
    }

    public function get(string $id): Product
    {
        return Product::with(['variants.attributes', 'bundleComponents.componentVariant'])->findOrFail($id);
    }

    /**
     * List products for an org.
     *
     * Products are ALWAYS stored at root org level (catalog is org-wide).
     * Any orgId passed (root or branch) resolves to the root so that:
     *   - Admin sees products they created ✅
     *   - Branch officer sees the full catalog ✅
     *   - Customer app sees the full catalog ✅
     *
     * Optional filter: $filters['branch_id'] has no effect here —
     * product catalog is global to the org tree.
     */
    public function listForOrg(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        $rootOrgId = $this->scope->rootId($orgId);

        return Product::where('org_id', $rootOrgId)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['type']),   fn($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['search']), fn($q) => $q->where('name', 'ilike', "%{$filters['search']}%"))
            ->with(['variants'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function publish(string $id): Product
    {
        $product = Product::findOrFail($id);
        if ($product->variants()->where('is_active', true)->count() === 0) {
            throw new \RuntimeException('Cannot publish a product with no active variants.');
        }
        $product->update(['status' => 'active']);
        return $product->fresh();
    }

    public function archive(string $id): Product
    {
        $product = Product::findOrFail($id);
        $product->update(['status' => 'archived']);
        return $product->fresh();
    }

    private function generateSlug(string $rootOrgId, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;
        while (Product::where('org_id', $rootOrgId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}