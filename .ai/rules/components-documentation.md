---
paths:
  - 'resources/js/components/documentation/**'
---

# Components Documentation

## Tiptap v3 StarterKit sudah memuat Link
Jangan tambahkan @tiptap/extension-link terpisah saat memakai StarterKit v3: StarterKit sudah membundel Link, Underline, dan TrailingNode. Menambahkannya lagi memicu error runtime 'Duplicate extension names found: [\'link\']'. Placeholder tetap perlu @tiptap/extension-placeholder.

## Mermaid wajib dynamic import
Jangan import mermaid di top-level komponen: ia menarik elk/cytoscape ratusan KB ke bundle awal. DocumentPreviewModal.vue memuatnya via dynamic import() saat pratinjau dibuka, lalu mermaid.run dengan securityLevel strict pada node .mermaid:not([data-processed]).

## Tabel & gambar editor Tiptap v3 (TableKit + Image)
Tabel memakai TableKit dari @tiptap/extension-table (bukan Table/TableRow/TableCell terpisah). Gambar memakai @tiptap/extension-image dengan allowBase64:false. Sisip gambar memakai ulang endpoint lampiran halaman (POST {pagesBase}/{id}/attachments) lalu setImage({src}); ada juga jalur sisip via URL. DocumentEditor menerima prop uploadUrl + csrf dari root. ContentValidationService sudah mengizinkan node table/tableRow/tableHeader/tableCell/image.
