@extends('layouts.app')

@section('title', 'Ringkasan Keuangan — InvoiceSummary')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-dark">Ringkasan Keuangan</h1>
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
            <select name="year" onchange="this.form.submit()"
                class="rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary">
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected((int) $selectedYear === (int) $year)>{{ $year }}</option>
                @endforeach
            </select>
            <select name="month" onchange="this.form.submit()"
                class="rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary">
                <option value="">Semua Bulan</option>
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}" @selected((string) $selectedMonth === (string) $num)>{{ $name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
        <x-summary-card label="Total Pendapatan (Paid)" :value="'Rp ' . number_format($totalRevenue, 0, ',', '.')">
            <x-slot name="badge">
                <span class="flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-success">
                    <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                    {{ $revenueGrowth }}%
                </span>
            </x-slot>
        </x-summary-card>

        <x-summary-card label="Total Piutang (Unpaid)" :value="'Rp ' . number_format($totalReceivable, 0, ',', '.')">
            <x-slot name="badge">
                <span class="rounded-full bg-amber-50 p-1 text-warning">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </span>
            </x-slot>
        </x-summary-card>

        <x-summary-card label="Total Volume Invoice" :value="$totalInvoices">
            <x-slot name="badge">
                <span class="rounded-full bg-slate-100 p-1 text-slate-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </span>
            </x-slot>
        </x-summary-card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="mb-4 text-lg font-bold text-dark">Grafik Pendapatan Bulanan</h3>
            <div class="h-72 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
            <h3 class="mb-4 text-lg font-bold text-dark">Invoice Terbaru</h3>
            <div class="flex-1 space-y-4">
                @forelse ($recentInvoices as $invoice)
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="text-sm font-semibold text-dark">{{ $invoice->invoice_number }}</p>
                            <p class="text-xs text-slate-500">{{ $invoice->customer->name ?? '-' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-dark">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
                            <x-status-badge :status="$invoice->status" class="mt-1" />
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada invoice.</p>
                @endforelse
            </div>

            <a href="#"
                class="mt-6 flex w-full items-center justify-center rounded-lg bg-dark py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-slate-800">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Unduh Rekap Laporan (PDF)
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('revenueChart').getContext('2d');

            const gradient = ctx.createLinearGradient(0, 0, 0, 280);
            gradient.addColorStop(0, '#2563eb');
            gradient.addColorStop(1, 'rgba(37, 99, 235, 0.1)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Pendapatan (Juta Rp)',
                        data: @json($chartData),
                        backgroundColor: gradient,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { family: 'Inter', weight: 'bold' },
                            bodyFont: { family: 'Inter' },
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function (context) {
                                    return 'Rp ' + context.parsed.y + '.000.000';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: {
                                color: '#94a3b8',
                                font: { family: 'Inter', size: 12 },
                                callback: function (value) { return 'Rp ' + value + 'jt'; }
                            }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: {
                                color: '#64748b',
                                font: { family: 'Inter', size: 12, weight: '500' }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
