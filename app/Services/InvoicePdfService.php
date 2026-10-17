<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use App\Support\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    /**
     * @return array<string, ?string>
     */
    public function settings(): array
    {
        return Setting::getMany([
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
    }

    public function filename(Invoice $invoice): string
    {
        return str_replace(['/', '\\'], '-', $invoice->invoice_number).'.pdf';
    }

    public function resourceUri(Invoice $invoice): string
    {
        return "invoice://{$invoice->id}/pdf";
    }

    public function bytes(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'settings' => $this->settings(),
            'terbilang' => Terbilang::make($invoice->total_amount),
        ])->setPaper('a4');

        return (string) $pdf->output();
    }
}
