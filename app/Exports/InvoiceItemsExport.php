<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Support\ExcelDownload;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class InvoiceItemsExport
{
    public static function spreadsheet(Invoice $invoice): Spreadsheet
    {
        $invoice->load(['customer', 'items']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Invoice');

        $sheet->fromArray([
            ['No. Invoice', $invoice->invoice_number],
            ['Pelanggan', $invoice->customer?->name ?? '-'],
            ['Tanggal Terbit', $invoice->invoice_date?->format('Y-m-d')],
            ['Jatuh Tempo', $invoice->due_date?->format('Y-m-d')],
            [],
            ['No', 'Keterangan', 'Kategori', 'Qty', 'Harga Satuan', 'Diskon', 'PPN', 'Total'],
        ], null, 'A1');

        $row = 7;

        foreach ($invoice->items as $index => $item) {
            $sheet->fromArray([[
                $index + 1,
                $item->item_name,
                $item->category ?? '-',
                $item->quantity,
                (float) $item->unit_price,
                (float) $item->discount_amount,
                (float) $item->tax_amount,
                (float) $item->total_price,
            ]], null, "A{$row}");

            $row++;
        }

        $sheet->fromArray([
            [],
            ['', '', '', '', '', '', 'Subtotal', (float) $invoice->subtotal],
            ['', '', '', '', '', '', 'Diskon', (float) $invoice->discount_amount],
            ['', '', '', '', '', '', 'PPN', (float) $invoice->tax_amount],
            ['', '', '', '', '', '', 'TOTAL TAGIHAN', (float) $invoice->total_amount],
        ], null, "A{$row}");

        $lastRow = $row + 4;
        ExcelDownload::styleHeader($sheet, 'A6:H6');
        ExcelDownload::styleBorders($sheet, "A6:H{$lastRow}");
        $sheet->getStyle("E7:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A'.($row + 4).':H'.($row + 4))->getFont()->setBold(true);

        foreach (range(1, 8) as $col) {
            $sheet->getColumnDimension(ExcelDownload::columnLetter($col))->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
