@extends('layouts.app')

@section('title', 'Invoice Saya — IceSum')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Portal Pelanggan</p>
        <h1 class="mt-1 text-2xl font-bold text-dark dark:text-white">Invoice Saya</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Daftar tagihan atas nama Anda.</p>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <form method="GET" action="{{ route('portal.invoices.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <input type="text" name="search" value="{{ request('search') }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:border-primary focus:bg-white focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                placeholder="Cari No. Invoice...">
            <select name="status" onchange="this.form.submit()"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm sm:w-48 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <option value="">Semua Status</option>
                <option value="PAID" @selected(request('status') === 'PAID')>Dibayar</option>
                <option value="UNPAID" @selected(request('status') === 'UNPAID')>Belum Dibayar</option>
                <option value="CANCELLED" @selected(request('status') === 'CANCELLED')>Batal</option>
            </select>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">No. Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-6 py-3">
                                <a href="{{ route('portal.invoices.show', $invoice) }}" class="font-semibold text-primary hover:underline">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $invoice->invoice_date?->format('d M Y') }}</td>
                            <td class="px-6 py-3"><x-status-badge :status="$invoice->status" /></td>
                            <td class="px-6 py-3 text-right text-sm font-semibold">Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-6 text-center text-sm italic text-slate-400">Belum ada invoice.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $invoices->links() }}</div>
@endsection
