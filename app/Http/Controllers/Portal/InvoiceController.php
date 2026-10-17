<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $customerId = $request->user()->customer_id;

        $invoices = Invoice::with('customer')
            ->where('customer_id', $customerId)
            ->when($request->query('search'), function ($query, $search): void {
                $query->where('invoice_number', 'like', "%{$search}%");
            })
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest('invoice_date')
            ->paginate(10)
            ->withQueryString();

        return view('portal.invoices.index', ['invoices' => $invoices]);
    }

    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless((int) $invoice->customer_id === (int) $request->user()->customer_id, 403);

        return view('portal.invoices.show', ['invoice' => $invoice->load(['customer', 'items'])]);
    }

    public function downloadPdf(Request $request, Invoice $invoice, InvoicePdfService $pdf): Response
    {
        abort_unless((int) $invoice->customer_id === (int) $request->user()->customer_id, 403);

        return response(
            $pdf->bytes($invoice),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$pdf->filename($invoice).'"',
            ]
        );
    }
}
