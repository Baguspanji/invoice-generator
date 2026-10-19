---
paths:
  - 'app/Http/Controllers/Documentation/**'
---

# Documentation

## Modul dokumentasi: staff-only, hard delete, disk public
Modul dokumentasi internal dibatasi middleware role:staff saja - tanpa Policy, project_user, atau akses customer. Halaman dihapus permanen (hard delete + cascade), bukan SoftDeletes. Lampiran memakai disk public di documentation-attachments/ mengikuti pola task_attachments. Keputusan final ada di docs/rancangan-dokumentasi.md.
