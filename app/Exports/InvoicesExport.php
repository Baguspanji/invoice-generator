<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Support\ExcelDownload;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class InvoicesExport
{
    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    public static function spreadsheet(Collection $invoices): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Invoice');

        $headings = ['No', 'No. Invoice', 'Pelanggan', 'Tipe', 'Tgl Terbit', 'Jatuh Tempo', 'Subtotal', 'Diskon', 'PPN', 'Total Tagihan', 'Status', 'Tgl Bayar'];
        $sheet->fromArray([$headings], null, 'A1');

        $row = 2;

        foreach ($invoices as $index => $invoice) {
            $status = $invoice->status instanceof \BackedEnum ? $invoice->status->value : (string) $invoice->status;
            $type = $invoice->customer?->type instanceof \BackedEnum ? $invoice->customer->type->label() : '-';

            $sheet->fromArray([[
                $index + 1,
                $invoice->invoice_number,
                $invoice->customer?->name ?? '-',
                $type,
                $invoice->invoice_date?->format('Y-m-d'),
                $invoice->due_date?->format('Y-m-d'),
                (float) $invoice->subtotal,
                (float) $invoice->discount_amount,
                (float) $invoice->tax_amount,
                (float) $invoice->total_amount,
                $status,
                $invoice->paid_at?->format('Y-m-d H:i'),
            ]], null, "A{$row}");

            $row++;
        }

        $lastRow = max($row - 1, 1);
        ExcelDownload::styleHeader($sheet, 'A1:L1');
        ExcelDownload::styleBorders($sheet, "A1:L{$lastRow}");
        $sheet->getStyle("G2:J{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        ExcelDownload::finish($sheet, 12, $lastRow);

        return $spreadsheet;
    }
}
