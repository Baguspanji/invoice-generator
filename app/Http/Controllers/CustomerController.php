<?php

namespace App\Http\Controllers;

use App\Enums\CustomerType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::withCount('invoices')
            ->when($request->query('search'), fn ($query, $search) => $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', ['customers' => $customers]);
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validated($request));
        $this->syncLoginAccount($request, $customer);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Pelanggan {$customer->name} berhasil ditambahkan.");
    }

    public function show(Customer $customer): View
    {
        $customer->loadCount('invoices');
        $customer->load(['users' => fn ($query) => $query->latest()]);

        $invoices = $customer->invoices()
            ->latest('invoice_date')
            ->paginate(10);

        return view('customers.show', [
            'customer' => $customer,
            'invoices' => $invoices,
        ]);
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['users' => fn ($query) => $query->latest()]);

        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request));
        $this->syncLoginAccount($request, $customer);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Pelanggan {$customer->name} berhasil diperbarui.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->invoices()->exists()) {
            return back()->withErrors([
                'customer' => 'Pelanggan tidak dapat dihapus karena masih memiliki invoice.',
            ]);
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', "Pelanggan {$customer->name} berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::enum(CustomerType::class)],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'create_account' => ['sometimes', 'boolean'],
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'account_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'account_password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);
    }

    private function syncLoginAccount(Request $request, Customer $customer): void
    {
        if (! $request->boolean('create_account')) {
            return;
        }

        $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'account_password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $email = $request->input('account_email') ?: $customer->email;

        if (! $email) {
            abort(422, 'Email pelanggan atau email akun wajib diisi untuk membuat akun login.');
        }

        if (User::where('email', $email)->exists()) {
            abort(422, 'Email akun sudah digunakan pengguna lain.');
        }

        User::create([
            'name' => $customer->name,
            'username' => $request->input('username'),
            'email' => $email,
            'password' => Hash::make((string) $request->input('account_password')),
            'role' => UserRole::CUSTOMER->value,
            'customer_id' => $customer->id,
        ]);
    }
}
