---
paths:
  - 'database/seeders/**'
---

# Seeders

## Demo data: idempotent seeder, aman dijalankan ulang
Contoh project + dokumentasi penuh dibuat oleh DocumentationDemoSeeder dan didaftarkan di DatabaseSeeder (otomatis jalan hanya di luar production). Idempotent: dilewati bila Project dengan name 'Aplikasi E-Invoice IceSum' sudah ada, jadi aman diulang tanpa menggandakan data. Lampiran contoh ditulis sebagai file nyata ke disk public (documentation-attachments/) memakai PNG 1x1; jalankan `php artisan db:seed --class=DocumentationDemoSeeder` untuk mengisi database yang sudah ada.
