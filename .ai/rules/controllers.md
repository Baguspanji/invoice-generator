---
paths:
  - app/Http/Controllers/DashboardController.php
---

# Controllers

## Dua basis tanggal: uang pakai paid_at, dokumen pakai invoice_date
Dua basis tanggal, jangan samakan diam-diam: KPI "Total Pendapatan" dan perbandingan growth memakai `paid_at` (arus kas masuk, harus konsisten dengan grafik bulanan yang juga `paid_at`), sedangkan "Total Piutang" dan "Jumlah Invoice Terbit" memakai `invoice_date` (dokumen terbit). Dropdown tahun dipilih dari union kedua tanggal. Query tanggal wajib sadar-driver lewat `datePartExpression()` (strftime untuk SQLite, YEAR/MONTH untuk MySQL) karena app jalan di kedua driver.
