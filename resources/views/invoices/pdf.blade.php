<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            margin: 40px 50px;
        }
        .text-center { text-align: center; }
        .title { font-size: 20pt; font-weight: bold; margin-bottom: 6px; }
        .subtitle { font-size: 12pt; margin-bottom: 30px; }
        .section-title { font-weight: bold; margin: 24px 0 10px 0; }
        table.meta td { padding: 1px 6px 1px 0; vertical-align: top; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border: 1px solid #000; padding: 5px 8px; }
        table.items th { font-weight: bold; }
        .col-no { width: 36px; text-align: center; }
        .col-qty { width: 80px; text-align: center; }
        .col-money { width: 130px; text-align: right; }
        .terbilang { font-style: italic; margin-top: 10px; }
        ul.payment { margin: 8px 0; padding-left: 28px; }
        ul.payment li { margin-bottom: 2px; }
        .note { font-style: italic; margin-top: 10px; }
        .signature { text-align: center; margin-top: 40px; }
        .signature .name { font-weight: bold; margin-top: 60px; }
    </style>
</head>
<body>
    <div class="text-center title">{{ $settings['invoice_title'] ?? 'INVOICE' }}</div>
    <div class="text-center subtitle">{{ $settings['invoice_subtitle'] ?? 'TAGIHAN PEMBAYARAN' }}</div>

    <table class="meta">
        <tr>
            <td>No. Invoice</td>
            <td>:</td>
            <td>{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>:</td>
            <td>{{ $invoice->invoice_date->translatedFormat('j F Y') }}</td>
        </tr>
        <tr>
            <td>Jatuh Tempo</td>
            <td>:</td>
            <td>{{ $invoice->due_date->translatedFormat('j F Y') }}</td>
        </tr>
    </table>

    <div class="section-title">DITAGIHKAN KEPADA:</div>
    <table class="meta">
        @if ($invoice->customer->isCompany())
            <tr>
                <td>Nama Instansi</td>
                <td>:</td>
                <td>{{ $invoice->customer->name }}</td>
            </tr>
        @else
            <tr>
                <td>No. KTP</td>
                <td>:</td>
                <td>{{ $invoice->customer->identity_number ?? '-' }}</td>
            </tr>
            <tr>
                <td>Nama</td>
                <td>:</td>
                <td>{{ $invoice->customer->name }}</td>
            </tr>
        @endif
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $invoice->customer->address ?? '-' }}</td>
        </tr>
        <tr>
            <td>No. HP</td>
            <td>:</td>
            <td>{{ $invoice->customer->phone ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">DARI:</div>
    <table class="meta">
        <tr>
            <td>No. KTP</td>
            <td>:</td>
            <td>{{ $settings['sender_id_number'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>Nama</td>
            <td>:</td>
            <td>{{ $settings['sender_name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $settings['sender_address'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>No. HP</td>
            <td>:</td>
            <td>{{ $settings['sender_phone'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">RINCIAN TAGIHAN</div>
    <table class="items">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th>Keterangan</th>
                <th class="col-qty">Kuantitas</th>
                <th class="col-money">Harga</th>
                <th class="col-money">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td><strong>{{ $item->item_name }}</strong></td>
                    <td class="col-qty">{{ $item->quantity }}</td>
                    <td class="col-money">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="col-money">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="col-no">&nbsp;</td>
                <td></td>
                <td class="col-qty"></td>
                <td class="col-money"><strong>SUBTOTAL</strong></td>
                <td class="col-money"><strong>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</strong></td>
            </tr>
            @if ((float) $invoice->discount_amount > 0)
                <tr>
                    <td class="col-no">&nbsp;</td>
                    <td></td>
                    <td class="col-qty"></td>
                    <td class="col-money"><strong>DISKON</strong></td>
                    <td class="col-money"><strong>Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</strong></td>
                </tr>
            @endif
            @if ((float) $invoice->tax_amount > 0)
                <tr>
                    <td class="col-no">&nbsp;</td>
                    <td></td>
                    <td class="col-qty"></td>
                    <td class="col-money"><strong>PPN</strong></td>
                    <td class="col-money"><strong>Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</strong></td>
                </tr>
            @endif
            <tr>
                <td class="col-no">&nbsp;</td>
                <td></td>
                <td class="col-qty"></td>
                <td class="col-money"><strong>TOTAL TAGIHAN</strong></td>
                <td class="col-money"><strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="terbilang">(Terbilang: {{ $terbilang }} Rupiah)</div>

    <div class="section-title">INSTRUKSI PEMBAYARAN</div>
    <p>Mohon lakukan pembayaran melalui transfer bank ke rekening berikut:</p>
    <ul class="payment">
        <li><strong>Nama Bank:</strong> {{ $settings['bank_name'] ?? '-' }}</li>
        <li><strong>No. Rekening:</strong> {{ $settings['bank_account_number'] ?? '-' }}</li>
        <li><strong>Atas Nama:</strong> {{ $settings['bank_account_name'] ?? '-' }}</li>
    </ul>
    <p class="note">{{ $settings['payment_note'] ?? '' }} Nomor Invoice ({{ $invoice->invoice_number }}).</p>

    <div class="signature">
        <div><strong>Hormat saya,</strong></div>
        <div class="name">{{ $settings['sender_name'] ?? '' }}</div>
    </div>
</body>
</html>
