<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use App\Support\Terbilang;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('terbilang mengubah angka menjadi kata bahasa Indonesia', function () {
    expect(Terbilang::make(0))->toBe('Nol');
    expect(Terbilang::make(500000))->toBe('Lima Ratus Ribu');
    expect(Terbilang::make(200000))->toBe('Dua Ratus Ribu');
    expect(Terbilang::make(5250000))->toBe('Lima Juta Dua Ratus Lima Puluh Ribu');
    expect(Terbilang::make(1000))->toBe('Seribu');
    expect(Terbilang::make(11))->toBe('Sebelas');
});

test('tamu diarahkan ke login saat mengunduh PDF invoice', function () {
    $invoice = Invoice::factory()->create();

    $this->get(route('invoices.pdf', $invoice))->assertRedirect('/login');
});

test('pengguna terotentikasi dapat mengunduh PDF invoice', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();

    $response = $this->actingAs($user)->get(route('invoices.pdf', $invoice));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect(strlen($response->getContent()))->toBeGreaterThan(1000);
});

test('PDF invoice memuat data pelanggan perseorangan dan pengaturan pengirim', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->individual()->create(['name' => 'Sofi Ansori']);
    $invoice = Invoice::factory()->for($customer)->create();

    Setting::set('sender_name', 'Muhammad Bagus Panji');

    $html = view('invoices.pdf', [
        'invoice' => $invoice->load(['customer', 'items']),
        'settings' => Setting::getMany(['sender_id_number', 'sender_name', 'sender_address', 'sender_phone', 'bank_name', 'bank_account_number', 'bank_account_name', 'payment_note', 'invoice_title', 'invoice_subtitle']),
        'terbilang' => Terbilang::make($invoice->total_amount),
    ])->render();

    expect($html)
        ->toContain('Sofi Ansori')
        ->toContain($customer->identity_number)
        ->toContain('No. KTP')
        ->toContain('Muhammad Bagus Panji')
        ->toContain($invoice->invoice_number);
});

test('PDF invoice pelanggan perusahaan menampilkan nama instansi', function () {
    $customer = Customer::factory()->company()->create();
    $invoice = Invoice::factory()->for($customer)->create();

    $html = view('invoices.pdf', [
        'invoice' => $invoice->load(['customer', 'items']),
        'settings' => [],
        'terbilang' => Terbilang::make($invoice->total_amount),
    ])->render();

    expect($html)->toContain('Perusahaan');
    // Tidak ada "No. KTP" sama sekali pada invoice perusahaan.
    expect(substr_count($html, 'No. KTP'))->toBe(0);
});

test('pengaturan dapat diperbarui dan dipakai PDF', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('settings.update'), [
        'settings' => ['sender_name' => 'Nama Baru Pengirim'],
    ]);

    $response->assertRedirect();
    expect(Setting::get('sender_name'))->toBe('Nama Baru Pengirim');

    $this->actingAs($user)->get(route('settings.edit'))->assertOk();
});

test('tanda tangan base64 tersimpan dan tampil pada PDF', function () {
    $user = User::factory()->create();
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    $this->actingAs($user)->put(route('settings.update'), [
        'settings' => ['signature_image' => $signature],
    ])->assertRedirect();

    expect(Setting::get('signature_image'))->toBe($signature);

    $invoice = Invoice::factory()->create();

    $html = view('invoices.pdf', [
        'invoice' => $invoice->load(['customer', 'items']),
        'settings' => Setting::getMany(['sender_name', 'signature_image']),
        'terbilang' => Terbilang::make($invoice->total_amount),
    ])->render();

    expect($html)->toContain($signature);

    $this->actingAs($user)
        ->get(route('invoices.pdf', $invoice))
        ->assertOk();
});
