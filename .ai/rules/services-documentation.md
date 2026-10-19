---
paths:
  - 'app/Services/Documentation/**'
---

# Services Documentation

## Render Tiptap JSON hanya lewat HtmlRenderer/MarkdownRenderer
Konten editor disimpan sebagai JSON mentah. Untuk HTML (pratinjau v-html, PDF) selalu pakai HtmlRenderer: ia meng-escape semua teks dan menyaring URL ke http/https/mailto/tel/relatif, jadi aman untuk v-html. Jangan pernah menyuntik konten mentah. Markdown lewat MarkdownRenderer. Keduanya punya depth guard (MAX_DEPTH) untuk JSON dalam.

## Activity log append-only + throttle autosave
Log aktivitas ada di tabel documentation_activity_logs (model DocumentationActivity, $table di-set manual; Laravel default salah ke documentation_activities). UPDATED_AT=null (append-only), page_id nullOnDelete agar riwayat bertahan setelah halaman dihapus. Update konten dari autosave memakai ActivityLogger::logThrottled (maks 1 entri per user/halaman per 15 menit) supaya log tidak membanjir.
