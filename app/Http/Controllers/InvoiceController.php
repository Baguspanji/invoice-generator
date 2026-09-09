<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Support\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::with('customer')
            ->when($request->query('search'), function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest('invoice_date')
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', ['invoices' => $invoices]);
    }

    public function create(): View
    {
        return view('invoices.create', [
            'customers' => Customer::orderBy('name')->get(),
            'categories' => ['Produk', 'Jasa', 'Lisensi', 'Konsultasi', 'Maintenance'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoice = DB::transaction(function () use ($validated) {
            $invoiceDate = Carbon::parse($validated['invoice_date']);

            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'invoice_number' => $this->nextInvoiceNumber($invoiceDate),
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'status' => InvoiceStatus::UNPAID,
            ]);

            $subtotal = 0;
            $discount = 0;
            $tax = 0;

            foreach ($validated['items'] as $row) {
                $lineSubtotal = $row['quantity'] * $row['unit_price'];
                $lineDiscount = (float) ($row['discount_amount'] ?? 0);
                $lineTax = (float) ($row['tax_amount'] ?? 0);

                $invoice->items()->create([
                    'item_name' => $row['item_name'],
                    'category' => $row['category'] ?? null,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'discount_amount' => $lineDiscount,
                    'tax_amount' => $lineTax,
                    'total_price' => $lineSubtotal - $lineDiscount + $lineTax,
                ]);

                $subtotal += $lineSubtotal;
                $discount += $lineDiscount;
                $tax += $lineTax;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $subtotal - $discount + $tax,
            ]);

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} berhasil dibuat.");
    }

    public function show(Invoice $invoice): View
    {
        return view('invoices.show', ['invoice' => $invoice->load(['customer', 'items'])]);
    }

    public function downloadPdf(Invoice $invoice): Response
    {
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
        ]);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $settings,
            'terbilang' => Terbilang::make($invoice->total_amount),
        ])->setPaper('a4');

        $filename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'.pdf';

        return $pdf->download($filename);
    }

    public function markAsPaid(Invoice $invoice): RedirectResponse
    {
        $invoice->markAsPaid();

        return back()->with('success', "Invoice {$invoice->invoice_number} ditandai lunas.");
    }

    public function markAsCancelled(Invoice $invoice): RedirectResponse
    {
        $invoice->markAsCancelled();

        return back()->with('success', "Invoice {$invoice->invoice_number} dibatalkan.");
    }

    private function nextInvoiceNumber(Carbon $date): string
    {
        $prefix = sprintf('INV/%s/%02d/', $date->format('Y'), (int) $date->format('m'));

        $last = Invoice::where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.sprintf('%04d', $sequence);
    }
}
