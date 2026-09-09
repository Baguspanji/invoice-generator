<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        $customer = Customer::create($validated);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Pelanggan {$customer->name} berhasil ditambahkan.");
    }

    public function show(Customer $customer): View
    {
        $customer->loadCount('invoices');

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
        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        $customer->update($validated);

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
}
