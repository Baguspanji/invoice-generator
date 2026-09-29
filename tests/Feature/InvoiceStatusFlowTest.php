<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice UNPAID dapat dibayar dan mencatat paid_at', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID, 'paid_at' => null]);

    $this->actingAs($user)
        ->from(route('invoices.show', $invoice))
        ->post(route('invoices.pay', $invoice))
        ->assertRedirect(route('invoices.show', $invoice));

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($invoice->paid_at)->not->toBeNull();
});

test('invoice UNPAID dapat dibatalkan', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID]);

    $this->actingAs($user)
        ->from(route('invoices.show', $invoice))
        ->post(route('invoices.cancel', $invoice))
        ->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::CANCELLED);
});

test('invoice PAID bersifat final: tidak dapat dibatalkan', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->paid()->create();

    $this->actingAs($user)
        ->from(route('invoices.show', $invoice))
        ->post(route('invoices.cancel', $invoice))
        ->assertRedirect(route('invoices.show', $invoice))
        ->assertSessionHasErrors('status');

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($invoice->paid_at)->not->toBeNull();
});

test('invoice CANCELLED tidak dapat dibayar ulang', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->cancelled()->create();

    $this->actingAs($user)
        ->from(route('invoices.show', $invoice))
        ->post(route('invoices.pay', $invoice))
        ->assertRedirect(route('invoices.show', $invoice))
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::CANCELLED);
});
