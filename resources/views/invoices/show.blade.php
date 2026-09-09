@extends('layouts.app')

@section('title', $invoice->invoice_number . ' — IceSum')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-dark dark:text-white">{{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $invoice->customer->name ?? '-' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-status-badge :status="$invoice->status" />
            <a href="{{ route('invoices.pdf', $invoice) }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                Unduh PDF
            </a>
            <a href="{{ route('invoices.excel', $invoice) }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                Export Excel
            </a>
            @if (($invoice->status instanceof \BackedEnum ? $invoice->status->value : $invoice->status) === 'UNPAID')
                <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="inline">
                    @csrf
                    <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        Tandai Lunas
                    </button>
                </form>
                <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" class="inline"
                    onsubmit="return confirm('Batalkan invoice ini?')">
                    @csrf
                    <button type="submit"
                        class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50 dark:border-red-900 dark:bg-slate-900 dark:hover:bg-red-950">
                        Batalkan
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div
        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Item</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Kategori</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Qty</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Harga Satuan</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-900">
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-dark dark:text-white">{{ $item->item_name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">{{ $item->category ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-slate-700 dark:text-slate-300">
                                {{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-right text-sm text-slate-700 dark:text-slate-300">Rp
                                {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-dark dark:text-white">Rp
                                {{ number_format($item->total_price, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                Tidak ada item.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div
            class="space-y-1 border-t border-slate-200 bg-slate-50 px-6 py-4 text-right text-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-slate-500 dark:text-slate-400">Subtotal: <span class="font-semibold text-dark dark:text-white">Rp
                    {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></p>
            <p class="text-slate-500 dark:text-slate-400">Diskon: <span class="font-semibold text-dark dark:text-white">Rp
                    {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span></p>
            <p class="text-slate-500 dark:text-slate-400">PPN: <span class="font-semibold text-dark dark:text-white">Rp
                    {{ number_format($invoice->tax_amount, 0, ',', '.') }}</span></p>
            <p class="text-base font-extrabold text-dark dark:text-white">Total: Rp
                {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
        </div>
    </div>

    <a href="{{ route('invoices.index') }}" class="mt-4 inline-block text-sm font-medium text-primary hover:text-blue-700">
        &larr; Kembali ke daftar invoice
    </a>
@endsection
