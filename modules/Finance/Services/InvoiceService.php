<?php

namespace Modules\Finance\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Contracts\Services\InvoiceServiceInterface;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceLineItem;
use Modules\Finance\Models\OrgSubscription;

class InvoiceService implements InvoiceServiceInterface
{
    public function create(array $data): Invoice
    {
        return DB::connection('finance')->transaction(function () use ($data) {
            $lineItems = $data['line_items'] ?? [];
            unset($data['line_items']);

            $data['invoice_number'] = $this->generateNumber();
            $data['status']         = $data['status'] ?? 'draft';

            // Compute totals from line items
            $subtotal       = 0;
            $taxAmount      = 0;
            $discountAmount = 0;

            foreach ($lineItems as &$item) {
                $item['subtotal']   = $item['quantity'] * $item['unit_price'];
                $item['tax_amount'] = $item['subtotal'] * ($item['tax_rate'] ?? 0);
                $item['total']      = $item['subtotal'] + $item['tax_amount'] - ($item['discount_amount'] ?? 0);
                $subtotal       += $item['subtotal'];
                $taxAmount      += $item['tax_amount'];
                $discountAmount += $item['discount_amount'] ?? 0;
            }

            $data['subtotal']        = $subtotal;
            $data['tax_amount']      = $taxAmount;
            $data['discount_amount'] = $discountAmount;
            $data['total']           = $subtotal + $taxAmount - $discountAmount;

            $invoice = Invoice::create($data);

            foreach ($lineItems as $i => $item) {
                $item['invoice_id']  = $invoice->id;
                $item['sort_order']  = $i;
                $item['currency']    = $data['currency'] ?? 'USD';
                InvoiceLineItem::create($item);
            }

            return $invoice->fresh(['lineItems']);
        });
    }

    public function get(string $id): Invoice
    {
        return Invoice::with(['lineItems', 'payment'])->findOrFail($id);
    }

    public function listForActor(string $actorId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Invoice::where(function ($q) use ($actorId) {
                $q->where('issuer_actor_id', $actorId)
                  ->orWhere('recipient_actor_id', $actorId);
            })
            ->when(isset($filters['status']),   fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['currency']), fn($q) => $q->where('currency', $filters['currency']))
            ->with(['lineItems'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function listForOrg(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        return Invoice::where('org_id', $orgId)
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->with(['lineItems'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function issue(string $id): Invoice
    {
        $invoice = Invoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            throw new \RuntimeException("Only draft invoices can be issued.");
        }

        $daysUntilDue = config('finance.invoice.due_days', 30);

        $invoice->update([
            'status'    => 'sent',
            'issued_at' => now(),
            'due_at'    => now()->addDays($daysUntilDue),
        ]);

        return $invoice->fresh();
    }

    public function markPaid(string $id, string $paymentId): Invoice
    {
        $invoice = Invoice::findOrFail($id);

        $invoice->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        return $invoice->fresh(['payment']);
    }

    public function cancel(string $id): Invoice
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->isPaid()) {
            throw new \RuntimeException("Cannot cancel a paid invoice. Use void instead.");
        }

        $invoice->update(['status' => 'cancelled']);
        return $invoice->fresh();
    }

    public function void(string $id): Invoice
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update(['status' => 'void']);
        return $invoice->fresh();
    }

    public function generateFromSubscription(string $orgId, string $subscriptionId): Invoice
    {
        $subscription = OrgSubscription::with('plan')->findOrFail($subscriptionId);

        return $this->create([
            'issuer_actor_id'    => config('finance.platform_actor_id', 'platform'),
            'recipient_actor_id' => $orgId,
            'org_id'             => $orgId,
            'source_type'        => 'OrgSubscription',
            'source_id'          => $subscriptionId,
            'currency'           => $subscription->plan->currency,
            'status'             => 'draft',
            'line_items'         => [[
                'description' => "Subscription: {$subscription->plan->name}",
                'quantity'    => 1,
                'unit_price'  => $subscription->plan->price,
                'tax_rate'    => 0,
            ]],
        ]);
    }

    public function generateNumber(): string
    {
        $prefix = config('finance.invoice.prefix', 'INV');
        $year   = now()->year;

        $last = Invoice::where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('created_at', 'desc')
            ->value('invoice_number');

        $seq = $last ? ((int) substr($last, -6)) + 1 : 1;

        return sprintf('%s-%d-%06d', $prefix, $year, $seq);
    }
}
