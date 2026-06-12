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

    public function index(Request $request, string $orgId): JsonResponse
    {
        $updates = ProductUpdate::where('org_id', $orgId)
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
    // Scope rules (derived from actual data model):
    //
    //   Customers are ALWAYS created under the root org_id.
    //   Branch association is carried via home_branch_id or assigned officer.
    //
    //   Root org promotion  → all customers where org_id = root_org_id
    //
    //   Branch promotion    → customers where:
    //                         (A) home_branch_id = branch_id
    //                         (B) assigned officer's current branch_id = branch_id
    //
    //   Customers with no home_branch_id and no officer assigned,
    //   or whose officer is at a different branch, are "unassigned"
    //   and are only reached by root org promotions.

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
     * Data model reality (confirmed from live data):
     *   All customers have org_id = root org ID regardless of branch.
     *   Branch association is only tracked via:
     *     - home_branch_id (explicit assignment)
     *     - assigned_officer_id → pm_officers.branch_id (via officer)
     *
     * Root org promotion:
     *   WHERE org_id = root_org_id
     *   Catches every customer — no ltree needed.
     *
     * Branch promotion:
     *   WHERE home_branch_id = branch_id          (Path A)
     *      OR assigned officer's branch_id = branch_id  (Path B)
     *
     *   Officer transfers automatically handled: we use officer's
     *   current branch_id. previous_branch_id is never consulted.
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

        // ── Branch: Path A (home_branch_id) + Path B (officer) ─
        $branchId = $update->org_id;

        // Resolve customer IDs via their officer's current branch (Path B).
        // Done as a separate query to keep the Eloquent builder clean.
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
                // Path A: explicitly assigned to this branch
                $q->where('home_branch_id', $branchId);

                // Path B: current officer works at this branch
                if (!empty($customerIdsViaOfficer)) {
                    $q->orWhereIn('id', $customerIdsViaOfficer);
                }
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