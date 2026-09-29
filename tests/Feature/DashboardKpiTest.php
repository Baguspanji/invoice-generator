<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('KPI pendapatan dashboard memakai paid_at, bukan invoice_date', function () {
    $user = User::factory()->create();

    // Terbit Sept 2026, dibayar Nov 2026 -> harus masuk KPI November, bukan September.
    Invoice::factory()->create([
        'invoice_date' => '2026-09-10',
        'status' => InvoiceStatus::PAID,
        'paid_at' => '2026-11-05 10:00:00',
        'total_amount' => 1_500_000,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?year=2026&month=11')
        ->assertOk()
        ->assertSee('Rp 1.500.000');

    $this->actingAs($user)
        ->get('/dashboard?year=2026&month=9')
        ->assertOk()
        ->assertDontSee('Rp 1.500.000');
});

test('KPI piutang dan jumlah invoice mengikuti invoice_date', function () {
    $user = User::factory()->create();

    Invoice::factory()->create([
        'invoice_date' => '2026-09-10',
        'due_date' => '2026-09-24',
        'status' => InvoiceStatus::UNPAID,
        'paid_at' => null,
        'total_amount' => 2_500_000,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?year=2026&month=9')
        ->assertOk()
        ->assertSee('Rp 2.500.000');
});

test('tahun dengan pendapatan tapi tanpa invoice terbit tetap bisa dipilih', function () {
    $user = User::factory()->create();

    Invoice::factory()->create([
        'invoice_date' => '2025-12-20',
        'status' => InvoiceStatus::PAID,
        'paid_at' => '2026-01-03 09:00:00',
        'total_amount' => 750_000,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?year=2026&month=1')
        ->assertOk()
        ->assertSee('Rp 750.000');
});
