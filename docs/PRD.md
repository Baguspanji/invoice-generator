# PRD Sederhana: Auto-Invoice & Revenue Summary System

## 1. Ringkasan Produk

Sistem ini dibuat untuk mencatat transaksi, menerbitkan file E-Invoice (PDF) secara otomatis, dan menyajikan ringkasan/laporan pendapatan (*Revenue Summary*) secara *real-time*. Designed untuk penggunaan langsung (tanpa sistem role/akses bertingkat) dengan fokus pada performa yang cepat dan efisiensi penyimpanan dokumen.

**Tech Stack Utama:**

* **Backend Framework:** Laravel
* **Database:** MySQL 8.0+
* **Storage:** Cloudflare R2 (Penyimpanan PDF Invoice)
* **Queue System:** Redis / Database Queue (Pemrosesan PDF Latar Belakang)

---

## 2. Fitur Utama & Kebutuhan Fungsional

### Modul A: Auto-Invoice Engine (Pembuat Invoice Otomatis)

1. **Penerbitan Invoice:**
* Input data pelanggan (Nama, Email, Alamat).
* Input daftar item (Nama Produk/Jasa, Jumlah, Harga Satuan, Diskon, PPN).
* Penomoran invoice otomatis berurutan (Contoh: `INV/2026/09/0001`).


2. **Status Invoice:**
* `Unpaid` $\rightarrow$ `Paid` $\rightarrow$ `Cancelled`.
* Pencatatan tanggal pembayaran saat status diubah menjadi `Paid`.


3. **Background PDF & Storage (Cloudflare R2):**
* Saat invoice disimpan, sistem menjalankan *Background Job* di Laravel untuk me-render PDF Invoice.
* File PDF diunggah langsung ke **Cloudflare R2**.
* Link unduh PDF menggunakan *Presigned URL* sementara agar aman dan hemat *bandwidth*.



---

### Modul B: Revenue Summary & Reporting (Ringkasan Pendapatan)

1. **Dashboard Indikator Utama (KPI):**
* **Total Pendapatan (Paid):** Total uang masuk dari invoice berstatus `Paid`.
* **Total Piutang (Unpaid):** Total tagihan yang belum dibayar.
* **Jumlah Invoice Terbit:** Statistik total invoice bulan/tahun ini.


2. **Ringkasan Pendapatan Periodik:**
* Filter grafik/laporan berdasarkan: **Harian, Bulanan, dan Tahunan**.
* Breakdown pendapatan per kategori produk atau jasa.


3. **Ekspor Laporan Pendapatan:**
* Ekspor ringkasan pendapatan ke file **Excel (.xlsx)** atau **PDF** untuk periode tertentu secara instan.



---

## 3. Rancangan Database MySQL (Simple Schema)

Untuk memastikan akurasi perhitungan pendapatan dan kecepatan query, berikut adalah struktur database sederhana yang dibutuhkan:

```sql
-- 1. Tabel Pelanggan
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 2. Tabel Utama Invoice
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('UNPAID', 'PAID', 'CANCELLED') DEFAULT 'UNPAID',
    paid_at TIMESTAMP NULL,
    pdf_r2_path VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX idx_invoice_search (status, invoice_date),
    INDEX idx_revenue_summary (status, paid_at)
);

-- 3. Tabel Detail Item Invoice
CREATE TABLE invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

```

---

## 4. Alur Kerja Sistem (Workflow)

```
[ Input Data Invoice ]
        │
        ▼
[ Simpan ke MySQL ] ──► Status: UNPAID
        │
        ├─────────────────────────────────────────┐
        ▼ (Sync)                                  ▼ (Async Background Job)
[ Tampilkan di Dashboard ]                [ Render PDF Invoice ]
                                                  │
                                                  ▼
                                       [ Upload ke Cloudflare R2 ]
                                                  │
                                                  ▼
                                     [ Update pdf_r2_path di DB ]

─────────────────────────────────────────────────────────────────────────

[ Pembayaran Diterima ] ──► Update Status: PAID & Set paid_at = NOW()
        │
        ▼
[ Summary Pendapatan / Dashboard Otomatis Terupdate ]

```

---

## 5. Implementasi Utama di Laravel

### A. Query Summary Pendapatan Bulanan (Cepat & Ringan)

Untuk mendapatkan summary pendapatan per bulan tanpa membebani server:

```php
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

// Mendapatkan total pendapatan per bulan di tahun berjalan
$monthlyRevenue = Invoice::select(
        DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month"),
        DB::raw("SUM(total_amount) as total_revenue"),
        DB::raw("COUNT(id) as total_invoices")
    )
    ->where('status', 'PAID')
    ->whereYear('paid_at', date('Y'))
    ->groupBy('month')
    ->orderBy('month', 'ASC')
    ->get();

```

### B. Job Upload PDF ke Cloudflare R2 (`GenerateAndUploadInvoicePdf.php`)

Proses pembuatan PDF dan pengunggahan ke Cloudflare R2 dilakukan di *Queue* agar respon aplikasi tetap instan saat user menekan tombol simpan.

```php
namespace App\Jobs;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateAndUploadInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice->load(['customer', 'items']);
    }

    public function handle()
    {
        // 1. Render PDF dari Blade View
        $pdf = Pdf::loadView('invoices.pdf_template', ['invoice' => $this->invoice]);
        $pdfContent = $pdf->output();

        // 2. Tentukan Path File di Cloudflare R2
        $filePath = "invoices/" . date('Y/m') . "/{$this->invoice->invoice_number}.pdf";

        // 3. Simpan ke Storage R2 (menggunakan disk 'r2' di config/filesystems.php)
        Storage::disk('r2')->put($filePath, $pdfContent);

        // 4. Update Path di Database
        $this->invoice->update([
            'pdf_r2_path' => $filePath
        ]);
    }
}

```

---

## 6. Nilai Efisiensi Arsitektur Ini

1. **Sederhana & Tanpa Overhead:** Tanpa arsitektur RBAC/Multi-role yang rumit, pengembangan bisa diselesaikan jauh lebih cepat.
2. **Bebas Biaya Transfer Data (Cloudflare R2):** Seluruh PDF invoice disimpan di Cloudflare R2. Karena R2 tidak mengenakan biaya *egress* (download), pengunduhan file invoice tidak akan menimbulkan pembengkakan biaya cloud.
3. **Pencatatan Presisi:** Menggunakan tipe data `DECIMAL(15,2)` pada MySQL untuk menjamin perhitungan rupiah 100% akurat tanpa error *floating point*.
