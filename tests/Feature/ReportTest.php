<?php

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

test('tamu diarahkan ke login saat membuka laporan', function () {
    $this->get('/reports')->assertRedirect('/login');
});

test('pengguna terotentikasi dapat melihat laporan pendapatan', function () {
    $user = User::factory()->create();
    Invoice::factory()->paid()->create();

    $this->actingAs($user)
        ->get('/reports')
        ->assertOk()
        ->assertSee('Laporan Pendapatan')
        ->assertSee('Breakdown per Kategori');
});

test('filter periode harian dan tahunan dapat ditampilkan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports?period=daily&year=2026&month=9')
        ->assertOk();

    $this->actingAs($user)
        ->get('/reports?period=yearly&year=2026')
        ->assertOk();
});

test('laporan dapat diekspor ke CSV', function () {
    $user = User::factory()->create();
    Invoice::factory()->paid()->create();

    $response = $this->actingAs($user)->get('/reports/export?period=monthly&year=2026');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    expect($response->streamedContent())->toContain('Pendapatan (Rp)');
});

test('laporan dapat diekspor ke Excel', function () {
    $user = User::factory()->create();
    Invoice::factory()->paid()->create();

    $response = $this->actingAs($user)->get('/reports/export-excel?period=monthly&year=2026');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheetml.sheet');

    $file = tempnam(sys_get_temp_dir(), 'report').'.xlsx';
    file_put_contents($file, $response->streamedContent());

    $spreadsheet = IOFactory::load($file);
    expect($spreadsheet->getSheetNames())->toContain('Rincian Periode', 'Per Kategori');
    expect($spreadsheet->getSheetByName('Rincian Periode')->getCell('A1')->getValue())->toBe('Periode');

    unlink($file);
});

test('rekap laporan dapat diunduh sebagai PDF', function () {
    $user = User::factory()->create();
    Invoice::factory()->paid()->create();

    $response = $this->actingAs($user)->get('/reports/recap?year=2026');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect(strlen($response->getContent()))->toBeGreaterThan(1000);
});

test('tamu diarahkan ke login saat mengekspor laporan', function () {
    $this->get('/reports/export-excel')->assertRedirect('/login');
    $this->get('/reports/recap')->assertRedirect('/login');
    $this->get('/invoices/export')->assertRedirect('/login');
});
