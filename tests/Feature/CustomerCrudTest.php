<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tamu diarahkan ke login saat membuka halaman pelanggan', function () {
    $this->get('/customers')->assertRedirect('/login');
});

test('pengguna terotentikasi dapat melihat daftar pelanggan', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['name' => 'PT Maju Jaya']);

    $this->actingAs($user)
        ->get('/customers')
        ->assertOk()
        ->assertSee('PT Maju Jaya');
});

test('pengguna dapat menambah pelanggan', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/customers', [
        'type' => 'COMPANY',
        'name' => 'CV Sumber Rejeki',
        'email' => 'halo@sumberrejeki.id',
        'phone' => '081234567890',
        'address' => 'Jl. Merdeka No. 1',
    ]);

    $customer = Customer::where('name', 'CV Sumber Rejeki')->first();
    expect($customer)->not->toBeNull();
    $response->assertRedirect(route('customers.show', $customer));
});

test('validasi tambah pelanggan membutuhkan nama', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/customers', ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('pengguna dapat memperbarui pelanggan', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->put(route('customers.update', $customer), [
        'type' => 'INDIVIDUAL',
        'identity_number' => '3514151110930003',
        'name' => 'Nama Baru',
        'email' => $customer->email,
        'phone' => $customer->phone,
        'address' => $customer->address,
    ]);

    $response->assertRedirect(route('customers.show', $customer));
    expect($customer->fresh()->name)->toBe('Nama Baru');
});

test('pengguna dapat menghapus pelanggan tanpa invoice', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    expect(Customer::find($customer->id))->toBeNull();
});

test('pelanggan dengan invoice tidak dapat dihapus', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();

    $response = $this->actingAs($user)->delete(route('customers.destroy', $invoice->customer));

    $response->assertSessionHasErrors('customer');
    expect(Customer::find($invoice->customer->id))->not->toBeNull();
});
