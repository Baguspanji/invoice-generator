<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', $this->buildReport($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->buildReport($request);
        $filename = sprintf(
            'laporan-pendapatan-%s-%s.csv',
            $data['period'],
            $data['period'] === 'daily' ? $data['year'].'-'.str_pad((string) $data['month'], 2, '0', STR_PAD_LEFT) : $data['year']
        );

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Periode', 'Jumlah Invoice', 'Pendapatan (Rp)']);

            foreach ($data['rows'] as $row) {
                fputcsv($handle, [$row['label'], $row['count'], $row['total']]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Kategori', 'Pendapatan (Rp)']);

            foreach ($data['categoryTotals'] as $category) {
                fputcsv($handle, [$category['label'], $category['total']]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'in:daily,monthly,yearly'],
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $period = $validated['period'] ?? 'monthly';
        $year = (int) ($validated['year'] ?? date('Y'));
        $month = (int) ($validated['month'] ?? date('n'));
        $category = $validated['category'] ?? '';

        $invoices = Invoice::with('items')
            ->where('status', InvoiceStatus::PAID)
            ->whereYear('paid_at', $year)
            ->when($period === 'daily', fn ($query) => $query->whereMonth('paid_at', $month))
            ->when($category !== '', fn ($query) => $query->whereHas('items', fn ($items) => $items->where('category', $category)))
            ->orderBy('paid_at')
            ->get();

        $buckets = $this->buckets($period, $year, $month);
        $rows = [];

        foreach ($buckets as $key => $label) {
            $rows[] = ['key' => $key, 'label' => $label, 'count' => 0, 'total' => 0.0];
        }

        $byKey = [];
        foreach ($rows as $index => $row) {
            $byKey[$row['key']] = $index;
        }

        foreach ($invoices as $invoice) {
            $key = $this->bucketKey($period, $invoice->paid_at);

            if (! isset($byKey[$key])) {
                continue;
            }

            $rows[$byKey[$key]]['count']++;
            $rows[$byKey[$key]]['total'] += (float) $invoice->total_amount;
        }

        $categoryTotals = InvoiceItem::select('category')
            ->selectRaw('SUM(total_price) as total')
            ->whereHas('invoice', fn ($query) => $query
                ->where('status', InvoiceStatus::PAID)
                ->whereYear('paid_at', $year)
                ->when($period === 'daily', fn ($inner) => $inner->whereMonth('paid_at', $month)))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->category ?? 'Tanpa Kategori',
                'total' => (float) $row->total,
            ])
            ->all();

        $totalRevenue = array_sum(array_column($rows, 'total'));
        $totalInvoices = array_sum(array_column($rows, 'count'));

        $categories = InvoiceItem::distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values()
            ->all();

        return [
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'category' => $category,
            'years' => $this->availableYears($year),
            'months' => $this->months(),
            'categories' => $categories,
            'rows' => $rows,
            'chartLabels' => array_column($rows, 'label'),
            'chartData' => array_map(fn ($row): float => round($row['total'] / 1000000, 2), $rows),
            'categoryTotals' => $categoryTotals,
            'totalRevenue' => $totalRevenue,
            'totalInvoices' => $totalInvoices,
            'averageTicket' => $totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buckets(string $period, int $year, int $month): array
    {
        if ($period === 'daily') {
            $days = Carbon::create($year, $month, 1)->daysInMonth;
            $buckets = [];

            foreach (range(1, $days) as $day) {
                $buckets[sprintf('%04d-%02d-%02d', $year, $month, $day)] = (string) $day;
            }

            return $buckets;
        }

        if ($period === 'yearly') {
            $buckets = [];

            foreach (range($year - 4, $year) as $y) {
                $buckets[(string) $y] = (string) $y;
            }

            return $buckets;
        }

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $buckets = [];

        foreach (range(1, 12) as $m) {
            $buckets[sprintf('%04d-%02d', $year, $m)] = $labels[$m - 1];
        }

        return $buckets;
    }

    private function bucketKey(string $period, ?Carbon $paidAt): string
    {
        if (! $paidAt) {
            return '';
        }

        return match ($period) {
            'daily' => $paidAt->format('Y-m-d'),
            'yearly' => $paidAt->format('Y'),
            default => $paidAt->format('Y-m'),
        };
    }

    /**
     * @return array<int, int>
     */
    private function availableYears(int $selectedYear): array
    {
        return Invoice::selectRaw('paid_at')
            ->whereNotNull('paid_at')
            ->pluck('paid_at')
            ->map(fn ($value): int => (int) Carbon::parse($value)->format('Y'))
            ->push($selectedYear, (int) date('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function months(): array
    {
        return [
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
    }
}
