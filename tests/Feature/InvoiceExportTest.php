<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

uses(RefreshDatabase::class);

function loadExcelResponse($response): Spreadsheet
{
    $file = tempnam(sys_get_temp_dir(), 'excel').'.xlsx';
    file_put_contents($file, $response->streamedContent());

    $spreadsheet = IOFactory::load($file);
    unlink($file);

    return $spreadsheet;
}

test('daftar invoice dapat diekspor ke Excel mengikuti filter', function () {
    $user = User::factory()->create();
    $paid = Invoice::factory()->paid()->create();
    Invoice::factory()->unpaid()->create();

    $response = $this->actingAs($user)->get('/invoices/export?status=PAID');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheetml.sheet');

    $sheet = loadExcelResponse($response)->getActiveSheet();
    expect($sheet->getCell('A1')->getValue())->toBe('No');
    expect($sheet->getCell('B2')->getValue())->toBe($paid->invoice_number);
    expect($sheet->getHighestRow())->toBe(2);
});

test('rintian invoice dapat diekspor ke Excel', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->has(InvoiceItem::factory()->count(2), 'items')->create();

    $response = $this->actingAs($user)->get(route('invoices.excel', $invoice));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheetml.sheet');

    $sheet = loadExcelResponse($response)->getActiveSheet();
    expect($sheet->getCell('A6')->getValue())->toBe('No');
    expect($sheet->getCell('B1')->getValue())->toBe($invoice->invoice_number);
});

test('tamu diarahkan ke login saat mengekspor Excel invoice', function () {
    $invoice = Invoice::factory()->create();

    $this->get('/invoices/export')->assertRedirect('/login');
    $this->get(route('invoices.excel', $invoice))->assertRedirect('/login');
});
