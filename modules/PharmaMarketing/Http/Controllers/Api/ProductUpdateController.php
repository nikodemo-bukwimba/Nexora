<?php

namespace Modules\PharmaMarketing\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PharmaMarketing\Jobs\SendProductUpdateToCustomer;
use Modules\PharmaMarketing\Models\Customer;
use Modules\PharmaMarketing\Models\ProductUpdate;
use Modules\PharmaMarketing\Models\ProductUpdateDelivery;
use Modules\Platform\Models\Organization;

class ProductUpdateController extends Controller
{
    // ── GET /api/v1/pharma/orgs/{orgId}/product-updates ───────
    //
    // Visibility rules:
    //   Root org promotion  → visible to the root org AND every branch.
    //   Branch promotion    → visible only to that branch.
    //
    // So when a branch requests its product-updates, we include
    // [branchId, rootOrgId]. When the root org requests its own,
    // [rootOrgId] is sufficient (root === rootOrgId, no duplication).

    public function index(Request $request, string $orgId): JsonResponse
    {
        $org = Organization::find($orgId);

        $orgIds = [$orgId];

        if ($org && $org->root_org_id && $org->root_org_id !== $orgId) {
            $orgIds[] = $org->root_org_id;
        }

        $updates = ProductUpdate::whereIn('org_id', array_unique($orgIds))
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 25));

        return response()->json($updates);
    }

    // ── POST /api/v1/pharma/orgs/{orgId}/product-updates ──────

    public function store(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'body'                => ['nullable', 'string'],
            'update_type'         => ['required', 'string', 'in:promotion,new_product,general'],
            'target_segment'      => ['required', 'string'],
            'send_sms'            => ['boolean'],
            'send_whatsapp'       => ['boolean'],
            'send_in_app'         => ['boolean'],
            'product_ids'         => ['nullable', 'array'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'start_date'          => ['nullable', 'date'],
            'end_date'            => ['nullable', 'date'],
        ]);

        $update = ProductUpdate::create(array_merge(
            $request->only([
                'title', 'body', 'update_type', 'target_segment',
                'send_sms', 'send_whatsapp', 'send_in_app',
                'product_ids', 'discount_percentage',
                'start_date', 'end_date',
            ]),
            [
                'org_id'     => $orgId,
                'created_by' => $request->user()->id,
                'status'     => 'draft',
            ]
        ));

        return response()->json(['message' => 'Product update created.', 'update' => $update], 201);
    }

    // ── GET /api/v1/pharma/product-updates/{id} ───────────────

    public function show(string $id): JsonResponse
    {
        $update = ProductUpdate::findOrFail($id);
        return response()->json(['update' => $update]);
    }

    // ── PATCH /api/v1/pharma/product-updates/{id} ─────────────

    public function update(Request $request, string $id): JsonResponse
    {
        $update = ProductUpdate::findOrFail($id);

        if (in_array($update->status, ['sending', 'sent'])) {
            return response()->json(['message' => 'Cannot edit a published update.'], 422);
        }

        $request->validate([
            'title'               => ['sometimes', 'string', 'max:255'],
            'body'                => ['nullable', 'string'],
            'send_sms'            => ['sometimes', 'boolean'],
            'send_whatsapp'       => ['sometimes', 'boolean'],
            'send_in_app'         => ['sometimes', 'boolean'],
            'product_ids'         => ['nullable', 'array'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'start_date'          => ['nullable', 'date'],
            'end_date'            => ['nullable', 'date'],
            'status'              => ['sometimes', 'string', 'in:draft,sending,sent,failed'],
        ]);

        $update->update($request->only([
            'title', 'body', 'send_sms', 'send_whatsapp', 'send_in_app',
            'product_ids', 'discount_percentage', 'start_date', 'end_date', 'status',
        ]));

        return response()->json(['message' => 'Product update updated.', 'update' => $update->fresh()]);
    }

    // ── POST /api/v1/pharma/product-updates/{id}/publish ──────
    //
    // Scope rules (updated):
    //
    //   Root org promotion  → ALL customers in the org tree
    //                         (WHERE org_id = root_org_id — every
    //                          customer record lives at the root, this
    //                          catches everyone regardless of branch).
    //
    //   Branch promotion    → customers where:
    //                         (A) assigned officer's CURRENT branch_id = branch_id  (Path A — wins)
    //                         (B) home_branch_id = branch_id, only if (A) doesn't apply (Path B — fallback)
    //
    //   Priority: officer's current branch > home_branch_id > (root reaches all).
    //   Officer transfers automatically handled: we use the officer's
    //   current branch_id. previous_branch_id is never consulted.
    //
    //   A customer with no assigned officer and no home_branch_id is
    //   only reached by root org promotions.

    public function publish(Request $request, string $id): JsonResponse
    {
        $update = ProductUpdate::findOrFail($id);

        if ($update->status !== 'draft') {
            return response()->json([
                'message' => "Cannot publish: current status is '{$update->status}'.",
            ], 422);
        }

        $customers = $this->resolveTargetCustomers($update);

        if ($customers->isEmpty()) {
            return response()->json([
                'message' => 'No eligible customers found for this update.',
            ], 422);
        }

        $deliveries = [];

        foreach ($customers as $customer) {
            $channels = $this->resolveChannelsForCustomer($update, $customer);

            foreach ($channels as $channel) {
                $recipientAddress = match ($channel) {
                    'sms'      => $customer->phone,
                    'whatsapp' => $customer->whatsapp_number ?? $customer->phone,
                    'in_app'   => $customer->platform_user_id,
                    default    => null,
                };

                if (empty($recipientAddress)) {
                    continue;
                }

                $delivery = ProductUpdateDelivery::create([
                    'product_update_id' => $update->id,
                    'customer_id'       => $customer->id,
                    'channel'           => $channel,
                    'status'            => 'pending',
                    'recipient_address' => $recipientAddress,
                    'retry_count'       => 0,
                ]);

                $deliveries[] = $delivery;
            }
        }

        if (empty($deliveries)) {
            return response()->json([
                'message' => 'No valid delivery addresses found for target customers.',
            ], 422);
        }

        $update->update([
            'status'           => 'sending',
            'total_recipients' => count($deliveries),
            'sent_at'          => now(),
        ]);

        foreach ($deliveries as $delivery) {
            SendProductUpdateToCustomer::dispatch(
                $delivery->fresh(),
                $update->fresh(),
                Customer::find($delivery->customer_id)
            );
        }

        return response()->json([
            'message'          => 'Product update published. SMS jobs queued.',
            'update'           => $update->fresh(),
            'total_recipients' => count($deliveries),
        ]);
    }

    // ── GET /api/v1/pharma/product-updates/{id}/stats ─────────

    public function stats(string $id): JsonResponse
    {
        $update = ProductUpdate::findOrFail($id);

        $stats = DB::connection('pharma_marketing')
            ->table('pm_product_update_deliveries')
            ->where('product_update_id', $id)
            ->selectRaw("
                COUNT(*)                                             AS total,
                SUM(CASE WHEN status = 'sent'    THEN 1 ELSE 0 END) AS sent,
                SUM(CASE WHEN status = 'failed'  THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
            ")
            ->first();

        return response()->json(['update' => $update, 'stats' => $stats]);
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Resolve target customers based on the org that created the update.
     *
     * Priority (updated):
     *   Root org promotion:
     *     WHERE org_id = root_org_id
     *     Catches every customer — no ltree needed.
     *
     *   Branch promotion:
     *     Path A (wins): assigned officer's CURRENT branch_id = branch_id
     *     Path B (fallback, only if Path A customer set doesn't already
     *             include them): home_branch_id = branch_id
     *
     *   A customer reached via Path A is NOT re-evaluated against Path B —
     *   officer assignment takes precedence over a stale home_branch_id.
     */
    private function resolveTargetCustomers(ProductUpdate $update): \Illuminate\Database\Eloquent\Collection
    {
        $org = Organization::findOrFail($update->org_id);

        // ── Root org: every customer in the system for this org ─
        if ($org->type === 'root') {
            $query = Customer::where('org_id', $update->org_id)
                ->where('status', 'active')
                ->whereNull('deleted_at');

            return $this->applySegmentFilters($query, $update)->get();
        }

        // ── Branch: Path A (officer, wins) + Path B (home_branch_id, fallback) ─
        $branchId = $update->org_id;

        // Path A: customers whose assigned officer's CURRENT branch matches.
        // These customers are fully resolved by officer assignment —
        // their home_branch_id is irrelevant even if it points elsewhere.
        $customerIdsViaOfficer = DB::connection('pharma_marketing')
            ->table('pm_customers as c')
            ->join('pm_officers as o', 'o.actor_id', '=', 'c.assigned_officer_id')
            ->where('o.branch_id', $branchId)
            ->whereNull('o.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('c.status', 'active')
            ->pluck('c.id')
            ->toArray();

        $query = Customer::where('status', 'active')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($branchId, $customerIdsViaOfficer) {
                // Path A: officer's current branch matches (wins)
                if (!empty($customerIdsViaOfficer)) {
                    $q->whereIn('id', $customerIdsViaOfficer);
                }

                // Path B (fallback): home_branch_id matches AND the
                // customer has no assigned officer (or wasn't already
                // captured by Path A). Excluding officer-assigned
                // customers here prevents a stale home_branch_id from
                // re-including someone whose officer moved elsewhere.
                $q->orWhere(function ($q2) use ($branchId, $customerIdsViaOfficer) {
                    $q2->where('home_branch_id', $branchId);

                    if (!empty($customerIdsViaOfficer)) {
                        $q2->whereNotIn('id', $customerIdsViaOfficer);
                    }

                    // Only customers with no officer currently mapped to
                    // a different branch should fall back to home_branch_id.
                    // (Customers with an officer at a DIFFERENT branch are
                    // excluded — their officer's branch wins for them too,
                    // they just aren't targeted by THIS branch's promotion.)
                    $q2->where(function ($q3) {
                        $q3->whereNull('assigned_officer_id')
                           ->orWhereNotExists(function ($sub) {
                               $sub->select('id')
                                   ->from('pm_officers')
                                   ->whereColumn('pm_officers.actor_id', 'pm_customers.assigned_officer_id')
                                   ->whereNull('pm_officers.deleted_at');
                           });
                    });
                });
            });

        return $this->applySegmentFilters($query, $update)->get();
    }

    /**
     * Apply optional segment filters from the update's target_filters JSON.
     */
    private function applySegmentFilters(
        \Illuminate\Database\Eloquent\Builder $query,
        ProductUpdate                         $update
    ): \Illuminate\Database\Eloquent\Builder {
        if ($update->target_segment === 'all') {
            return $query;
        }

        $filters = $update->target_filters ?? [];

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (!empty($filters['tier'])) {
            $query->where('tier', $filters['tier']);
        }
        if (!empty($filters['customer_type'])) {
            $query->where('customer_type', $filters['customer_type']);
        }

        return $query;
    }

    /**
     * Determine which channels to use for a specific customer,
     * respecting both the update's channel flags and the
     * customer's own communication preferences.
     */
    private function resolveChannelsForCustomer(
        ProductUpdate $update,
        Customer      $customer
    ): array {
        $channels = [];

        if ($update->send_sms && $customer->receives_sms && !empty($customer->phone)) {
            $channels[] = 'sms';
        }

        if ($update->send_whatsapp && $customer->receives_whatsapp) {
            $number = $customer->whatsapp_number ?? $customer->phone;
            if (!empty($number)) {
                $channels[] = 'whatsapp';
            }
        }

        if ($update->send_in_app && $customer->receives_in_app && !empty($customer->platform_user_id)) {
            $channels[] = 'in_app';
        }

        return $channels;
    }
}