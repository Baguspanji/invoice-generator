<?php

namespace App\Mcp\Resources;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Invoice PDF document. Read via invoice://{invoice_id}/pdf to download the PDF binary.')]
#[MimeType('application/pdf')]
class InvoicePdf extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('invoice://{invoice_id}/pdf');
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request, InvoicePdfService $pdf): Response
    {
        $request->validate([
            'invoice_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $invoice = Invoice::findOrFail($request->get('invoice_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Invoice not found.');
        }

        return Response::blob($pdf->bytes($invoice));
    }
}
