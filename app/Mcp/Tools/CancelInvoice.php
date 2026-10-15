<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Cancel an unpaid invoice. Only works on UNPAID invoices and cannot be undone.')]
class CancelInvoice extends Tool
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
            $invoice = Invoice::findOrFail($request->get('invoice_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Invoice not found.');
        }

        if ($invoice->status !== InvoiceStatus::UNPAID) {
            return Response::error('Only UNPAID invoices can be cancelled.');
        }

        $invoice->markAsCancelled();

        return Response::json([
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->fresh()->status->value,
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
