@extends('layouts.app')

@section('title', 'Daftar Invoice — IceSum')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-dark dark:text-white">Daftar Invoice</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Kelola dan pantau seluruh tagihan pelanggan</p>
        </div>
        <div class="flex items-center gap-2">
            <button
                class="flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition duration-150 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                    </path>
                </svg>
                Filter
            </button>
            <button
                class="flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition duration-150 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                Export Excel
            </button>
            <a href="{{ route('invoices.create') }}"
                class="flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-blue-700">
                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Invoice Baru
            </a>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <form method="GET" action="{{ route('invoices.index') }}"
            class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="relative w-full md:max-w-xs">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-3 text-sm focus:border-primary focus:bg-white focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:bg-slate-800"
                    placeholder="Cari No. Invoice / Pelanggan">
            </div>
            <div class="flex items-center gap-1 overflow-x-auto rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                @foreach (['' => 'Semua', 'PAID' => 'Dibayar', 'UNPAID' => 'Belum Dibayar', 'CANCELLED' => 'Batal'] as $value => $label)
                    <a href="{{ route('invoices.index', array_merge(request()->except('status', 'page'), $value !== '' ? ['status' => $value] : [])) }}"
                        class="{{ (string) request('status', '') === (string) $value ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:text-dark dark:text-slate-400 dark:hover:text-white' }} whitespace-nowrap rounded-md px-4 py-1.5 text-sm {{ (string) request('status', '') === (string) $value ? 'font-semibold' : 'font-medium' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </form>
    </div>

    <div
        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            No. Invoice</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Pelanggan</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Tanggal Terbit</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Jatuh Tempo</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Total Tagihan</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Status</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Akses/Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-900">
                    @forelse ($invoices as $invoice)
                        <tr class="transition duration-150 hover:bg-slate-50 dark:hover:bg-slate-800">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-primary">
                                {{ $invoice->invoice_number }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700 dark:text-slate-300">
                                {{ $invoice->customer->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                {{ $invoice->invoice_date?->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                {{ $invoice->due_date?->format('d/m/Y') }}</td>
                            <td
                                class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-dark dark:text-white">
                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <x-status-badge :status="$invoice->status" />
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('invoices.show', $invoice) }}"
                                        class="text-slate-500 hover:text-primary dark:text-slate-400" title="Lihat Detail">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                    </a>
                                    <a href="#" class="text-slate-500 hover:text-primary dark:text-slate-400"
                                        title="Unduh PDF">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </a>
                                    @if (($invoice->status instanceof \BackedEnum ? $invoice->status->value : $invoice->status) === 'UNPAID')
                                        <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="text-slate-500 hover:text-warning dark:text-slate-400"
                                                title="Tandai Lunas">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('invoices.cancel', $invoice) }}"
                                            class="inline" onsubmit="return confirm('Batalkan invoice ini?')">
                                            @csrf
                                            <button type="submit"
                                                class="text-slate-500 hover:text-red-600 dark:text-slate-400"
                                                title="Batalkan">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                Belum ada invoice.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div
            class="flex flex-col items-center justify-between gap-4 border-t border-slate-200 bg-white px-6 py-4 sm:flex-row dark:border-slate-700 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Menampilkan <span
                    class="font-semibold text-dark dark:text-white">{{ $invoices->firstItem() ?? 0 }}-{{ $invoices->lastItem() ?? 0 }}</span>
                dari <span class="font-semibold text-dark dark:text-white">{{ $invoices->total() }}</span> invoice
            </p>
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
