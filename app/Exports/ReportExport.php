<?php

namespace App\Exports;

use App\Support\ExcelDownload;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ReportExport
{
    /**
     * @param  array<string, mixed>  $data  Hasil dari ReportController::buildReport().
     */
    public static function spreadsheet(array $data): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $period = $spreadsheet->getActiveSheet();
        $period->setTitle('Rincian Periode');
        $period->fromArray([['Periode', 'Jumlah Invoice', 'Pendapatan (Rp)']], null, 'A1');

        $row = 2;

        foreach ($data['rows'] as $item) {
            $period->fromArray([[$item['label'], $item['count'], (float) $item['total']]], null, "A{$row}");
            $row++;
        }

        $period->fromArray([['Total', $data['totalInvoices'], (float) $data['totalRevenue']]], null, "A{$row}");

        $lastRow = max($row, 2);
        ExcelDownload::styleHeader($period, 'A1:C1');
        ExcelDownload::styleBorders($period, "A1:C{$lastRow}");
        $period->getStyle("C2:C{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $period->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        ExcelDownload::finish($period, 3, $lastRow);

        $category = $spreadsheet->createSheet();
        $category->setTitle('Per Kategori');
        $category->fromArray([['Kategori', 'Pendapatan (Rp)']], null, 'A1');

        $row = 2;

        foreach ($data['categoryTotals'] as $item) {
            $category->fromArray([[$item['label'], (float) $item['total']]], null, "A{$row}");
            $row++;
        }

        $lastRow = max($row - 1, 1);
        ExcelDownload::styleHeader($category, 'A1:B1');
        ExcelDownload::styleBorders($category, "A1:B{$lastRow}");
        $category->getStyle("B2:B{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        ExcelDownload::finish($category, 2, $lastRow);

        return $spreadsheet;
    }
}
