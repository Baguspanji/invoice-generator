<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Pendapatan {{ $year }}</title>
    <style>
        @page { margin: 36px 44px 60px 44px; }

        @font-face {
            font-family: 'Nunito';
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/Nunito-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Nunito';
            font-style: normal;
            font-weight: bold;
            src: url('{{ storage_path('fonts/Nunito-Bold.ttf') }}') format('truetype');
        }

        body {
            font-family: 'Nunito', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.5;
        }

        .title { font-size: 20pt; font-weight: bold; color: #0f172a; margin: 0; }
        .subtitle { font-size: 10pt; color: #64748b; margin: 2px 0 0 0; }
        .accent-bar { height: 4px; background: #2563eb; margin: 14px 0 0 0; }

        .kpi-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .kpi-table td { width: 33.33%; padding-right: 10px; vertical-align: top; }
        .kpi-table td.last { padding-right: 0; }
        .kpi {
            background: #f1f5f9;
            border-top: 3px solid #2563eb;
            border-radius: 0 0 10px 10px;
            padding: 10px 14px;
        }
        .kpi .kpi-label { font-size: 8.5pt; color: #64748b; margin: 0; }
        .kpi .kpi-value { font-size: 14pt; font-weight: bold; color: #0f172a; margin: 2px 0 0 0; }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin: 24px 0 0 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
        }
        table.data {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            border: 1px solid #dbe3ef;
            border-radius: 12px;
        }
        table.data th {
            background: #2563eb;
            color: #ffffff;
            font-size: 9pt;
            padding: 10px 12px;
            text-align: left;
        }
        table.data th.r-tl { border-radius: 11px 0 0 0; }
        table.data th.r-tr { border-radius: 0 11px 0 0; }
        table.data th.num, table.data td.num { text-align: right; }
        table.data td { padding: 9px 12px; font-size: 9.5pt; border-top: 1px solid #e8eef7; }
        table.data tr.alt td { background: #f3f7fd; }
        table.data tr.last td { border-bottom: none; }
        table.data tr.last td.r-bl { border-radius: 0 0 0 11px; }
        table.data tr.last td.r-br { border-radius: 0 0 11px 0; }
        table.data tr.total td { font-weight: bold; background: #0f172a; color: #ffffff; }
        table.data tr.total td.r-bl { border-radius: 0 0 0 11px; }
        table.data tr.total td.r-br { border-radius: 0 0 11px 0; }

        .footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .footer .pagenum:before { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        {{ $senderName }} &bull; Rekap Pendapatan {{ $year }} &bull;
        Dicetak {{ $printedAt }} &bull; Halaman <span class="pagenum"></span>
    </div>

    <p class="title">Rekap Pendapatan</p>
    <p class="subtitle">Tahun {{ $year }} &bull; {{ $senderName }}</p>
    <div class="accent-bar"></div>

    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi">
                    <p class="kpi-label">Total Pendapatan</p>
                    <p class="kpi-value">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <p class="kpi-label">Invoice Lunas</p>
                    <p class="kpi-value">{{ $totalInvoices }}</p>
                </div>
            </td>
            <td class="last">
                <div class="kpi">
                    <p class="kpi-label">Rata-rata / Invoice</p>
                    <p class="kpi-value">Rp {{ number_format($averageTicket, 0, ',', '.') }}</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Pendapatan Bulanan {{ $year }}</div>
    <table class="data">
        <thead>
            <tr>
                <th class="r-tl">BULAN</th>
                <th class="num">JUMLAH INVOICE</th>
                <th class="num r-tr">PENDAPATAN</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($monthly as $row)
                <tr class="{{ $loop->even ? 'alt' : '' }}">
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                    <td class="num">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td class="r-bl">TOTAL</td>
                <td class="num">{{ $totalInvoices }}</td>
                <td class="num r-br">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Breakdown per Kategori</div>
    <table class="data">
        <thead>
            <tr>
                <th class="r-tl">KATEGORI</th>
                <th class="num r-tr">PENDAPATAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categoryTotals as $row)
                <tr class="{{ $loop->even ? 'alt' : '' }}{{ $loop->last ? ' last' : '' }}">
                    <td class="{{ $loop->last ? 'r-bl' : '' }}">{{ $row['label'] }}</td>
                    <td class="num{{ $loop->last ? ' r-br' : '' }}">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr class="last">
                    <td class="r-bl" colspan="2" style="text-align: center; color: #64748b;">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
