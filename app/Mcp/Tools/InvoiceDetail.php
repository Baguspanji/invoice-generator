<?php

namespace App\Mcp\Tools;

use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Show a single invoice with customer, totals and line items.')]
#[IsReadOnly]
class InvoiceDetail extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'invoice_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $invoice = Invoice::with(['customer', 'items'])->findOrFail($request->get('invoice_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Invoice not found.');
        }

        return Response::json([
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer' => $invoice->customer?->name,
            'invoice_date' => $invoice->invoice_date?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'subtotal' => (float) $invoice->subtotal,
            'discount_amount' => (float) $invoice->discount_amount,
            'tax_amount' => (float) $invoice->tax_amount,
            'total_amount' => (float) $invoice->total_amount,
            'status' => $invoice->status->value,
            'items' => $invoice->items->map(fn ($item) => [
                'item_name' => $item->item_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ])->values()->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice_id' => $schema->integer()->description('ID of the invoice')->required()->min(1),
        ];
    }
}
