<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/login', 'auth.login')->name('login');

Route::get('/dashboard', function (Request $request) {
    $selectedYear = (int) $request->query('year', date('Y'));
    $selectedMonth = $request->query('month', '');

    $years = Invoice::selectRaw('DISTINCT YEAR(invoice_date) as year')
        ->orderByDesc('year')
        ->pluck('year')
        ->map(fn ($year): int => (int) $year)
        ->push((int) date('Y'))
        ->unique()
        ->sortDesc()
        ->values()
        ->all();

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $totalRevenue = Invoice::whereYear('invoice_date', $selectedYear)
        ->when($selectedMonth !== '', fn ($query) => $query->whereMonth('invoice_date', (int) $selectedMonth))
        ->where('status', InvoiceStatus::PAID)
        ->sum('total_amount');

    $totalReceivable = Invoice::whereYear('invoice_date', $selectedYear)
        ->when($selectedMonth !== '', fn ($query) => $query->whereMonth('invoice_date', (int) $selectedMonth))
        ->where('status', InvoiceStatus::UNPAID)
        ->sum('total_amount');

    $totalInvoices = Invoice::whereYear('invoice_date', $selectedYear)
        ->when($selectedMonth !== '', fn ($query) => $query->whereMonth('invoice_date', (int) $selectedMonth))
        ->count();

    $previousRevenue = Invoice::whereYear('invoice_date', $selectedYear - 1)
        ->where('status', InvoiceStatus::PAID)
        ->sum('total_amount');

    $revenueGrowth = $previousRevenue > 0
        ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
        : 0;

    $monthlyTotals = Invoice::select(
        DB::raw('MONTH(paid_at) as month'),
        DB::raw('SUM(total_amount) as total')
    )
        ->where('status', InvoiceStatus::PAID)
        ->whereYear('paid_at', $selectedYear)
        ->groupBy('month')
        ->pluck('total', 'month');

    $shortLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $chartData = [];
    foreach (range(1, 12) as $month) {
        $chartData[] = round((float) ($monthlyTotals[$month] ?? 0) / 1000000, 2);
    }

    $recentInvoices = Invoice::with('customer')
        ->latest('invoice_date')
        ->limit(5)
        ->get();

    return view('dashboard', [
        'years' => $years,
        'months' => $months,
        'selectedYear' => $selectedYear,
        'selectedMonth' => $selectedMonth,
        'totalRevenue' => (float) $totalRevenue,
        'totalReceivable' => (float) $totalReceivable,
        'totalInvoices' => $totalInvoices,
        'revenueGrowth' => $revenueGrowth,
        'chartLabels' => $shortLabels,
        'chartData' => $chartData,
        'recentInvoices' => $recentInvoices,
    ]);
})->name('dashboard');

Route::get('/invoices', function (Request $request) {
    $invoices = Invoice::with('customer')
        ->when($request->query('search'), function ($query, $search): void {
            $query->where(function ($inner) use ($search): void {
                $inner->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
            });
        })
        ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
        ->latest('invoice_date')
        ->paginate(10)
        ->withQueryString();

    return view('invoices.index', ['invoices' => $invoices]);
})->name('invoices.index');

Route::view('/invoices/create', 'invoices.create')->name('invoices.create');

Route::get('/invoices/{invoice}', function (Invoice $invoice) {
    return view('invoices.show', ['invoice' => $invoice->load(['customer', 'items'])]);
})->name('invoices.show');
