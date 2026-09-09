<?php

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
