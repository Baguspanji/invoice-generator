<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Setting;
use App\Support\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function downloadPdf(Request $request, Invoice $invoice): Response
    {
        abort_unless((int) $invoice->customer_id === (int) $request->user()->customer_id, 403);

        $invoice->load(['customer', 'items']);

        $settings = Setting::getMany([
            'sender_id_number',
            'sender_name',
            'sender_address',
            'sender_phone',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'payment_note',
            'invoice_title',
            'invoice_subtitle',
            'signature_image',
        ]);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
            'terbilang' => Terbilang::make($invoice->total_amount),
        ])->setPaper('a4');

        $filename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'.pdf';

        return $pdf->stream($filename);
    }
}
