---
paths:
  - 'app/Http/Middleware/**'
---

# Middleware

## Status invoice: Paid itu final, Cancelled hanya dari Unpaid
Status invoice bercabang dari UNPAID, bukan dua tahap: UNPAID → PAID atau UNPAID → CANCELLED. Middleware `invoice.unpaid` menolak semua perubahan status dari PAID/CANCELLED, jadi PAID itu final. Jangan longgarkan jadi PAID → CANCELLED tanpa status baru (mis. REFUNDED) + kolom penyeimbangnya, karena paid_at sudah jadi dasar rekap pendapatan dan pembatalan akan menghapus uang yang sudah masuk. Diuji di tests/Feature/InvoiceStatusFlowTest.php.
