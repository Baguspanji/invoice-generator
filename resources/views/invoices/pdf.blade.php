<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
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

        @page {
            margin: 36px 44px 60px 44px;
        }

        body {
            font-family: 'Nunito', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.5;
        }

        /* ===== Header ===== */
        .header {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: top;
            padding: 0;
        }

        .brand-name {
            font-size: 17pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }

        .brand-sub {
            font-size: 9pt;
            color: #64748b;
            margin: 0;
        }

        .invoice-title {
            font-size: 30pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            text-align: right;
            letter-spacing: 2px;
        }

        .invoice-number {
            font-size: 10pt;
            color: #2563eb;
            text-align: right;
            margin: 0;
            font-weight: bold;
        }

        .accent-bar {
            height: 4px;
            background: #2563eb;
            margin: 14px 0 0 0;
        }

        /* ===== Info boxes ===== */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .info-table td {
            vertical-align: top;
            padding: 0;
        }

        .box {
            background: #f1f5f9;
            border-top: 3px solid #2563eb;
            border-radius: 0 0 12px 12px;
            padding: 10px 14px;
        }

        .box-label {
            font-size: 8.5pt;
            font-weight: bold;
            color: #2563eb;
            margin: 0 0 6px 0;
            letter-spacing: 1px;
        }

        .box-name {
            font-size: 11.5pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .box p {
            margin: 0;
            padding: 0;
            font-size: 9.5pt;
            color: #475569;
            line-height: 1;
        }

        .meta-table {
            border-collapse: collapse;
            margin-left: auto;
        }

        .meta-table td {
            padding: 1px 0 1px 10px;
            font-size: 9.5pt;
            vertical-align: top;
            line-height: 1.35;
        }

        .meta-table .meta-label {
            color: #64748b;
            text-align: right;
        }

        .meta-table .meta-value {
            color: #0f172a;
            font-weight: bold;
            text-align: right;
        }

        .status {
            display: inline-block;
            vertical-align: middle;
            font-size: 9pt;
            font-weight: bold;
            border-radius: 20px;
            padding: 0 10px;
        }

        .status-paid {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .status-unpaid {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
        }

        .status-cancelled {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        /* ===== Items ===== */
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
        }

        table.items {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 8px;
            border: 1px solid #dbe3ef;
            border-radius: 12px;
        }

        table.items th {
            background: #2563eb;
            color: #ffffff;
            font-size: 9pt;
            padding: 0px 10px 4px 10px;
            text-align: left;
            letter-spacing: 0.5px;
            border: none;
        }

        table.items th.r-tl {
            border-radius: 11px 0 0 0;
        }

        table.items th.r-tr {
            border-radius: 0 11px 0 0;
        }

        table.items td {
            padding: 4px 10px;
            font-size: 9.5pt;
            line-height: 1;
            border-top: 1px solid #e8eef7;
        }

        table.items tr.alt td {
            background: #f3f7fd;
        }

        table.items tr.last td {
            border-bottom: none;
        }

        table.items tr.last td.r-bl {
            border-radius: 0 0 0 11px;
        }

        table.items tr.last td.r-br {
            border-radius: 0 0 11px 0;
        }

        .col-no {
            width: 34px;
            text-align: center;
            color: #64748b;
        }

        .col-qty {
            width: 70px;
            text-align: center;
        }

        .col-money {
            width: 120px;
            text-align: right;
            white-space: nowrap;
        }

        .item-name {
            font-weight: bold;
            color: #0f172a;
        }

        .item-cat {
            font-size: 8pt;
            color: #64748b;
            line-height: 1.2;
        }

        /* ===== Totals ===== */
        .totals-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .totals-wrap td {
            vertical-align: top;
            padding: 0;
            line-height: 0.65;
        }

        .terbilang-box {
            background: #eff6ff;
            border-left: 3px solid #2563eb;
            border-radius: 0 10px 10px 0;
            padding: 8px 12px;
            font-size: 9pt;
            font-style: italic;
            color: #1e40af;
            line-height: 1.2;
        }

        table.totals {
            border-collapse: collapse;
            margin-left: auto;
            width: 280px;
        }

        table.totals td {
            padding: 5px 10px;
            font-size: 9.5pt;
        }

        table.totals .t-label {
            color: #64748b;
        }

        table.totals .t-value {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
            white-space: nowrap;
        }

        table.totals tr.grand td {
            background: #0f172a;
            color: #ffffff;
            font-size: 11pt;
            padding: 9px 10px;
        }

        table.totals tr.grand .t-label {
            color: #ffffff;
        }

        /* ===== Payment ===== */
        .payment-box {
            border: 1px solid #e2e8f0;
            border-left: 4px solid #2563eb;
            border-radius: 0 12px 12px 0;
            background: #ffffff;
            padding: 12px 16px;
            margin-top: 10px;
        }

        .payment-box table td {
            padding: 2px 8px 2px 0;
            font-size: 9.5pt;
            vertical-align: top;
            line-height: .65;
        }

        .payment-note {
            font-size: 9pt;
            font-style: italic;
            color: #475569;
            margin: 8px 0 0 0;
        }

        /* ===== Signature & footer ===== */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 36px;
        }

        .sign-table td {
            vertical-align: top;
        }

        .sign-block {
            text-align: center;
            font-size: 10pt;
        }

        .sign-image {
            height: 55px;
            margin: 6px 0 2px 0;
        }

        .sign-name {
            font-weight: bold;
            margin-top: 0px;
            color: #0f172a;
        }

        .sign-name.no-image {
            margin-top: 64px;
        }

        .sign-role {
            font-size: 9pt;
            color: #64748b;
        }

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

        .footer .pagenum:before {
            content: counter(page);
        }

        .footer .pagecount:before {
            content: counter(pages);
        }
    </style>
</head>

<body>
    <div class="footer">
        {{ $settings['sender_name'] ?? '' }} &bull; {{ $invoice->invoice_number }} &bull;
        Halaman <span class="pagenum"></span> dari <span class="pagecount"></span>
    </div>

    <!-- Header -->
    <table class="header">
        <tr>
            <td style="width: 58%;">
                <p class="brand-name">{{ $settings['sender_name'] ?? 'IceSum' }}</p>
                <p class="brand-sub">
                    {{ $settings['sender_address'] ?? '' }}<br>
                    {{ $settings['sender_phone'] ?? '' }}
                </p>
            </td>
            <td style="width: 42%;">
                <p class="invoice-title">INVOICE</p>
                <p class="invoice-number">{{ $invoice->invoice_number }}</p>
            </td>
        </tr>
    </table>
    <div class="accent-bar"></div>

    <!-- Billed to + meta -->
    <table class="info-table">
        <tr>
            <td style="width: 55%;">
                <div class="box">
                    <p class="box-label">DITAGIHKAN KEPADA</p>
                    <p class="box-name">{{ $invoice->customer->name }}</p>
                    @if ($invoice->customer->isCompany())
                        <p>Perusahaan</p>
                    @elseif ($invoice->customer->identity_number)
                        <p>No. KTP: {{ $invoice->customer->identity_number }}</p>
                    @endif
                    @if ($invoice->customer->address)
                        <p>{{ $invoice->customer->address }}</p>
                    @endif
                    @if ($invoice->customer->phone)
                        <p>{{ $invoice->customer->phone }}</p>
                    @endif
                    @if ($invoice->customer->email)
                        <p>{{ $invoice->customer->email }}</p>
                    @endif
                </div>
            </td>
            <td style="width: 45%; padding-left: 16px;">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Tanggal Terbit</td>
                        <td class="meta-value">{{ $invoice->invoice_date->translatedFormat('j F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Jatuh Tempo</td>
                        <td class="meta-value">{{ $invoice->due_date->translatedFormat('j F Y') }}</td>
                    </tr>
                    @if ($invoice->paid_at)
                        <tr>
                            <td class="meta-label">Tanggal Bayar</td>
                            <td class="meta-value">{{ $invoice->paid_at->translatedFormat('j F Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="meta-label">Status</td>
                        <td class="meta-value">
                            @php
                                $statusValue =
                                    $invoice->status instanceof \BackedEnum
                                        ? $invoice->status->value
                                        : $invoice->status;
                                $statusClass =
                                    $statusValue === 'PAID'
                                        ? 'status-paid'
                                        : ($statusValue === 'UNPAID'
                                            ? 'status-unpaid'
                                            : 'status-cancelled');
                            @endphp
                            <span class="status {{ $statusClass }}">{{ $statusValue }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($settings['invoice_subtitle'] ?? false)
        <p style="font-size: 9.5pt; color: #475569; margin: 12px 0 0 0;">{{ $settings['invoice_subtitle'] }}</p>
    @endif

    <!-- Items -->
    <div class="section-title">Rincian Tagihan</div>
    <table class="items">
        <thead>
            <tr>
                <th class="col-no r-tl">NO</th>
                <th>KETERANGAN</th>
                <th class="col-qty" style="text-align: center;">QTY</th>
                <th class="col-money" style="text-align: right;">HARGA</th>
                <th class="col-money" style="text-align: right;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr class="{{ $loop->even ? 'alt' : '' }}{{ $loop->last ? ' last' : '' }}">
                    <td class="col-no{{ $loop->last ? ' r-bl' : '' }}">{{ $loop->iteration }}</td>
                    <td>
                        <div class="item-name">{{ $item->item_name }}</div>
                        @if ($item->category)
                            <div class="item-cat">{{ $item->category }}</div>
                        @endif
                    </td>
                    <td class="col-qty">{{ $item->quantity }}</td>
                    <td class="col-money">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="col-money{{ $loop->last ? ' r-br' : '' }}">Rp
                        {{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals-wrap">
        <tr>
            <td style="width: 42%;">
                <div class="terbilang-box">Terbilang: {{ $terbilang }} Rupiah</div>
            </td>
            <td style="width: 58%;">
                <table class="totals">
                    <tr>
                        <td class="t-label">Subtotal</td>
                        <td class="t-value">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if ((float) $invoice->discount_amount > 0)
                        <tr>
                            <td class="t-label">Diskon</td>
                            <td class="t-value">&minus; Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                    @if ((float) $invoice->tax_amount > 0)
                        <tr>
                            <td class="t-label">PPN</td>
                            <td class="t-value">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td class="t-label">TOTAL TAGIHAN</td>
                        <td class="t-value">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Payment -->
    <div class="section-title">Instruksi Pembayaran</div>
    <div class="payment-box">
        <table>
            <tr>
                <td style="color: #64748b;">Nama Bank</td>
                <td><strong>{{ $settings['bank_name'] ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td style="color: #64748b;">No. Rekening</td>
                <td><strong>{{ $settings['bank_account_number'] ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td style="color: #64748b;">Atas Nama</td>
                <td><strong>{{ $settings['bank_account_name'] ?? '-' }}</strong></td>
            </tr>
        </table>
        <p class="payment-note">{{ $settings['payment_note'] ?? '' }} Nomor Invoice ({{ $invoice->invoice_number }}).
        </p>
    </div>

    <!-- Signature -->
    <table class="sign-table">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%;">
                <div class="sign-block">
                    <div>Hormat saya,</div>
                    @if (!empty($settings['signature_image']))
                        <img src="{{ $settings['signature_image'] }}" alt="Signature" class="sign-image">
                    @endif
                    <div class="sign-name{{ empty($settings['signature_image']) ? ' no-image' : '' }}">
                        {{ $settings['sender_name'] ?? '' }}</div>
                </div>
            </td>
        </tr>
    </table>
</body>

</html>
