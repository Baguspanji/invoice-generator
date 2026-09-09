<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelDownload
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public static function stream(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => self::MIME]);
    }

    public static function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
        ]);
    }

    public static function styleBorders(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);
    }

    public static function finish(Worksheet $sheet, int $columnCount, int $rowCount): void
    {
        $lastColumn = self::columnLetter($columnCount);

        foreach (range(1, $columnCount) as $col) {
            $sheet->getColumnDimension(self::columnLetter($col))->setAutoSize(true);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$rowCount}");
    }

    public static function columnLetter(int $column): string
    {
        $letter = '';

        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $letter = chr(65 + $remainder).$letter;
            $column = intdiv($column - 1, 26);
        }

        return $letter;
    }
}
