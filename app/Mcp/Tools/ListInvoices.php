<?php

namespace App\Mcp\Tools;

use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List invoices with customer, dates, totals and payment status.')]
#[IsReadOnly]
class ListInvoices extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'in:PAID,UNPAID,CANCELLED'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $invoices = Invoice::with('customer')
            ->when($request->get('search'), function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->get('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest('invoice_date')
            ->limit((int) ($request->get('limit') ?? 20))
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer' => $invoice->customer?->name,
                'invoice_date' => $invoice->invoice_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'total_amount' => (float) $invoice->total_amount,
                'status' => $invoice->status->value,
            ])
            ->values()
            ->all();

        return Response::json(['invoices' => $invoices]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by status: PAID, UNPAID or CANCELLED')->max(10),
            'search' => $schema->string()->description('Filter by invoice number or customer name')->max(255),
            'limit' => $schema->integer()->description('Max invoices to return')->min(1)->max(50),
        ];
    }
}
