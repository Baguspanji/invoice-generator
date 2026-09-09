@extends('layouts.app')

@section('title', $customer->name . ' — IceSum')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-dark dark:text-white">{{ $customer->name }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $customer->email ?? 'Tanpa email' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customers.edit', $customer) }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                Ubah
            </a>
            <a href="{{ route('customers.index') }}" class="text-sm font-medium text-primary hover:text-blue-700">
                &larr; Daftar pelanggan
            </a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-1">
            <h3 class="mb-4 text-lg font-bold text-dark dark:text-white">Kontak</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Email</dt>
                    <dd class="text-dark dark:text-white">{{ $customer->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Telepon</dt>
                    <dd class="text-dark dark:text-white">{{ $customer->phone ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Alamat</dt>
                    <dd class="text-dark dark:text-white">{{ $customer->address ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-slate-400">Total Invoice</dt>
                    <dd class="text-dark dark:text-white">{{ $customer->invoices_count }} invoice</dd>
                </div>
            </dl>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 md:col-span-2">
            <h3 class="border-b border-slate-200 px-6 py-4 text-lg font-bold text-dark dark:border-slate-700 dark:text-white">Invoice Pelanggan</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">No. Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Terbit</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-900">
                        @forelse ($invoices as $invoice)
                            <tr class="transition duration-150 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-primary">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="hover:underline">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">{{ $invoice->invoice_date?->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-dark dark:text-white">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <x-status-badge :status="$invoice->status" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada invoice untuk pelanggan ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($invoices->hasPages())
                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-700">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
