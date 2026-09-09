<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('root URL mengarah ke dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
});

test('tamu diarahkan ke login saat membuka halaman terproteksi', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/invoices')->assertRedirect('/login');
});

test('halaman login dapat ditampilkan', function () {
    $this->get('/login')->assertOk()->assertSee('Masuk ke Sistem');
});

test('pengguna dapat login dengan kredensial valid', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('login gagal dengan kredensial salah', function () {
    User::factory()->create(['email' => 'admin@perusahaan.com']);

    $response = $this->post('/login', [
        'email' => 'admin@perusahaan.com',
        'password' => 'salah',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('pengguna terotentikasi dapat membuka dashboard dan daftar invoice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
    $this->actingAs($user)->get('/invoices')->assertOk();
});

test('invoice unpaid dapat ditandai lunas', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->unpaid()->create();

    $response = $this->actingAs($user)->post(route('invoices.pay', $invoice));

    $response->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
    expect($invoice->fresh()->paid_at)->not->toBeNull();
});

test('middleware menolak perubahan status invoice yang sudah paid', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->paid()->create();

    $response = $this->actingAs($user)->post(route('invoices.pay', $invoice));

    $response->assertSessionHasErrors('status');
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

test('pengguna dapat logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
