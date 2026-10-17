<?php

namespace App\Mcp\Tools;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Download an invoice as PDF. Returns invoice metadata plus a resource_link to invoice://{invoice_id}/pdf; read that resource to get the PDF binary.')]
#[IsReadOnly]
class DownloadInvoicePdf extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, InvoicePdfService $pdf): Response|ResponseFactory
    {
        $request->validate([
            'invoice_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $invoice = Invoice::with(['customer'])->findOrFail($request->get('invoice_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Invoice not found.');
        }

        $bytes = $pdf->bytes($invoice);
        $filename = $pdf->filename($invoice);
        $uri = $pdf->resourceUri($invoice);

        return Response::make([
            Response::json([
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer' => $invoice->customer?->name,
                'total_amount' => (float) $invoice->total_amount,
                'status' => $invoice->status->value,
                'filename' => $filename,
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($bytes),
                'resource_uri' => $uri,
            ]),
            Response::resourceLink(
                $uri,
                name: $filename,
                mimeType: 'application/pdf',
                title: "Invoice {$invoice->invoice_number}",
                description: 'PDF document of the invoice. Read this resource to download the binary.',
                size: strlen($bytes),
            ),
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
