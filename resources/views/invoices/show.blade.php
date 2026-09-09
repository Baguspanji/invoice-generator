@extends('layouts.app')

@section('title', $invoice->invoice_number . ' — InvoiceSummary')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-dark">{{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-slate-500">{{ $invoice->customer->name ?? '-' }}</p>
        </div>
        <x-status-badge :status="$invoice->status" />
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Kategori</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Qty</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Harga Satuan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-dark">{{ $item->item_name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ $item->category ?? '-' }}</td>
                            <td class="px-6 py-4 text-right text-sm text-slate-700">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-right text-sm text-slate-700">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-dark">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">Tidak ada item.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="space-y-1 border-t border-slate-200 bg-slate-50 px-6 py-4 text-right text-sm">
            <p class="text-slate-500">Subtotal: <span class="font-semibold text-dark">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></p>
            <p class="text-slate-500">Diskon: <span class="font-semibold text-dark">Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span></p>
            <p class="text-slate-500">PPN: <span class="font-semibold text-dark">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</span></p>
            <p class="text-base font-extrabold text-dark">Total: Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
        </div>
    </div>

    <a href="{{ route('invoices.index') }}" class="mt-4 inline-block text-sm font-medium text-primary hover:text-blue-700">
        &larr; Kembali ke daftar invoice
    </a>
@endsection
