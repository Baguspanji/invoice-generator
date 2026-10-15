@extends('layouts.app')

@section('title', $invoice->invoice_number . ' — Portal — IceSum')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('portal.invoices.index') }}" class="text-sm font-medium text-slate-500 hover:text-primary dark:text-slate-400">
                &larr; Kembali ke Invoice Saya
            </a>
            <h1 class="mt-1 text-2xl font-bold text-dark dark:text-white">{{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $invoice->customer->name ?? '-' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-status-badge :status="$invoice->status" />
            <a href="{{ route('portal.invoices.pdf', $invoice) }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                Unduh PDF
            </a>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Qty</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Harga Satuan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td class="px-6 py-3 text-sm font-medium">{{ $item->item_name }}</td>
                            <td class="px-6 py-3 text-right text-sm">{{ $item->quantity }}</td>
                            <td class="px-6 py-3 text-right text-sm">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-right text-sm font-semibold">Rp {{ number_format((float) $item->total_price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-sm font-semibold">Total</td>
                        <td class="px-6 py-3 text-right text-sm font-bold">Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
