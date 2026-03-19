<?php

namespace Modules\Finance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Contracts\Services\InvoiceServiceInterface;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceServiceInterface $invoices) {}

    /** POST /api/v1/finance/invoices */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'issuer_actor_id'    => ['required', 'string'],
            'recipient_actor_id' => ['required', 'string'],
            'currency'           => ['required', 'string', 'size:3'],
            'line_items'         => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string'],
            'line_items.*.quantity'    => ['required', 'integer', 'min:1'],
            'line_items.*.unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = $this->invoices->create($request->all());
        return response()->json(['message' => 'Invoice created.', 'invoice' => $invoice], 201);
    }

    /** GET /api/v1/finance/invoices/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json($this->invoices->get($id));
    }

    /** GET /api/v1/finance/actors/{actorId}/invoices */
    public function forActor(Request $request, string $actorId): JsonResponse
    {
        return response()->json(
            $this->invoices->listForActor($actorId, $request->only(['status', 'currency']), (int) $request->get('per_page', 25))
        );
    }

    /** GET /api/v1/finance/orgs/{orgId}/invoices */
    public function forOrg(Request $request, string $orgId): JsonResponse
    {
        return response()->json(
            $this->invoices->listForOrg($orgId, $request->only(['status']), (int) $request->get('per_page', 25))
        );
    }

    /** POST /api/v1/finance/invoices/{id}/issue */
    public function issue(string $id): JsonResponse
    {
        $invoice = $this->invoices->issue($id);
        return response()->json(['message' => 'Invoice issued.', 'invoice' => $invoice]);
    }

    /** POST /api/v1/finance/invoices/{id}/cancel */
    public function cancel(string $id): JsonResponse
    {
        $invoice = $this->invoices->cancel($id);
        return response()->json(['message' => 'Invoice cancelled.', 'invoice' => $invoice]);
    }
}
