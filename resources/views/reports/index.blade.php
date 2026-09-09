@extends('layouts.app')

@section('title', 'Laporan Pendapatan — IceSum')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-dark dark:text-white">Laporan Pendapatan</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Ringkasan periodik harian, bulanan, dan tahunan</p>
        </div>
        <div class="flex items-center gap-2 print:hidden">
            <a href="{{ route('reports.export', request()->query()) }}"
                class="flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition duration-150 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export CSV
            </a>
            <button type="button" onclick="window.print()"
                class="flex items-center rounded-lg bg-dark px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-slate-800 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-white">
                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak
            </button>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 print:hidden">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-1 overflow-x-auto rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                @foreach (['daily' => 'Harian', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $value => $label)
                    <a href="{{ route('reports.index', array_merge(request()->except('period'), ['period' => $value])) }}"
                        class="{{ $period === $value ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:text-dark dark:text-slate-400 dark:hover:text-white' }} whitespace-nowrap rounded-md px-4 py-1.5 text-sm {{ $period === $value ? 'font-semibold' : 'font-medium' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select name="year" onchange="this.form.submit()"
                    class="rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endforeach
                </select>
                @if ($period === 'daily')
                    <select name="month" onchange="this.form.submit()"
                        class="rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        @foreach ($months as $num => $name)
                            <option value="{{ $num }}" @selected($month === $num)>{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="category" onchange="this.form.submit()"
                    class="rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
        <x-summary-card label="Total Pendapatan" :value="'Rp ' . number_format($totalRevenue, 0, ',', '.')" />
        <x-summary-card label="Jumlah Invoice Lunas" :value="$totalInvoices" />
        <x-summary-card label="Rata-rata per Invoice" :value="'Rp ' . number_format($averageTicket, 0, ',', '.')" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 lg:col-span-2">
            <h3 class="mb-4 text-lg font-bold text-dark dark:text-white">
                Grafik Pendapatan {{ $period === 'daily' ? 'Harian' : ($period === 'yearly' ? 'Tahunan' : 'Bulanan') }} {{ $year }}
            </h3>
            <div class="h-72 w-full">
                <canvas id="reportChart"></canvas>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 lg:col-span-1">
            <h3 class="mb-4 text-lg font-bold text-dark dark:text-white">Breakdown per Kategori</h3>
            <div class="space-y-4">
                @forelse ($categoryTotals as $item)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-600 dark:text-slate-300">{{ $item['label'] }}</span>
                            <span class="font-semibold text-dark dark:text-white">Rp {{ number_format($item['total'], 0, ',', '.') }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-primary"
                                style="width: {{ $totalRevenue > 0 ? round($item['total'] / $totalRevenue * 100, 1) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada data kategori.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h3 class="border-b border-slate-200 px-6 py-4 text-lg font-bold text-dark dark:border-slate-700 dark:text-white">Rincian Periode</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Periode</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Jumlah Invoice</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-900">
                    @foreach ($rows as $row)
                        <tr class="transition duration-150 hover:bg-slate-50 dark:hover:bg-slate-800">
                            <td class="whitespace-nowrap px-6 py-3 text-sm font-medium text-dark dark:text-white">{{ $row['label'] }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-slate-600 dark:text-slate-300">{{ $row['count'] }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-dark dark:text-white">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <td class="whitespace-nowrap px-6 py-3 text-sm font-bold text-dark dark:text-white">Total</td>
                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-bold text-dark dark:text-white">{{ $totalInvoices }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-bold text-dark dark:text-white">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('reportChart').getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? '#1e293b' : '#f1f5f9';

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
                            grid: { color: gridColor, drawBorder: false },
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
