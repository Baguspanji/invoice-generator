<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('customer dapat login dengan email dan diarahkan ke portal', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->create([
        'email' => 'pelanggan@example.com',
        'username' => 'pelanggan1',
        'role' => UserRole::CUSTOMER->value,
        'customer_id' => $customer->id,
    ]);

    $response = $this->post('/login', [
        'login' => 'pelanggan@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('portal.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('customer dapat login dengan username', function () {
    $customer = Customer::factory()->create();
    User::factory()->create([
        'email' => 'pelanggan2@example.com',
        'username' => 'pelangganunik',
        'role' => UserRole::CUSTOMER->value,
        'customer_id' => $customer->id,
    ]);

    $response = $this->post('/login', [
        'login' => 'pelangganunik',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('portal.dashboard'));
    $this->assertAuthenticated();
});

test('login lama dengan field email tetap kompatibel', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('customer hanya melihat proyek miliknya', function () {
    $ownCustomer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $ownProject = Project::factory()->create(['customer_id' => $ownCustomer->id, 'name' => 'Proyek Milik Saya']);
    $otherProject = Project::factory()->create(['customer_id' => $otherCustomer->id, 'name' => 'Proyek Orang Lain']);

    $customerUser = User::factory()->create([
        'role' => UserRole::CUSTOMER->value,
        'customer_id' => $ownCustomer->id,
    ]);

    $this->actingAs($customerUser)
        ->get(route('portal.projects.index'))
        ->assertOk()
        ->assertSee('Proyek Milik Saya')
        ->assertDontSee('Proyek Orang Lain');

    $this->actingAs($customerUser)
        ->get(route('portal.projects.show', $ownProject))
        ->assertOk();

    $this->actingAs($customerUser)
        ->get(route('portal.projects.show', $otherProject))
        ->assertForbidden();
});

test('customer hanya melihat invoice miliknya', function () {
    $ownCustomer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $ownInvoice = Invoice::factory()->create(['customer_id' => $ownCustomer->id]);
    $otherInvoice = Invoice::factory()->create(['customer_id' => $otherCustomer->id]);

    $customerUser = User::factory()->create([
        'role' => UserRole::CUSTOMER->value,
        'customer_id' => $ownCustomer->id,
    ]);

    $this->actingAs($customerUser)
        ->get(route('portal.invoices.index'))
        ->assertOk()
        ->assertSee($ownInvoice->invoice_number)
        ->assertDontSee($otherInvoice->invoice_number);

    $this->actingAs($customerUser)
        ->get(route('portal.invoices.show', $ownInvoice))
        ->assertOk();

    $this->actingAs($customerUser)
        ->get(route('portal.invoices.show', $otherInvoice))
        ->assertForbidden();
});

test('customer tidak dapat mengakses area staff', function () {
    $customer = Customer::factory()->create();
    $customerUser = User::factory()->create([
        'role' => UserRole::CUSTOMER->value,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($customerUser)->get('/dashboard')->assertForbidden();
    $this->actingAs($customerUser)->get('/projects')->assertForbidden();
    $this->actingAs($customerUser)->get('/invoices')->assertForbidden();
    $this->actingAs($customerUser)->get('/customers')->assertForbidden();
});

test('staff tidak dapat mengakses portal customer', function () {
    $staff = User::factory()->create(['role' => UserRole::STAFF->value]);

    $this->actingAs($staff)->get(route('portal.dashboard'))->assertForbidden();
    $this->actingAs($staff)->get(route('portal.projects.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('portal.invoices.index'))->assertForbidden();
});

test('staff dapat membuatkan akun portal saat membuat pelanggan', function () {
    $staff = User::factory()->create(['role' => UserRole::STAFF->value]);

    $response = $this->actingAs($staff)->post(route('customers.store'), [
        'type' => 'INDIVIDUAL',
        'name' => 'Pelanggan Portal',
        'email' => 'portal@example.com',
        'create_account' => 1,
        'username' => 'portaluser',
        'account_password' => 'rahasia123',
    ]);

    $customer = Customer::where('name', 'Pelanggan Portal')->first();
    expect($customer)->not->toBeNull();
    $response->assertRedirect(route('customers.show', $customer));

    $account = User::where('username', 'portaluser')->first();
    expect($account)->not->toBeNull();
    expect($account->isCustomer())->toBeTrue();
    expect((int) $account->customer_id)->toBe((int) $customer->id);
});
