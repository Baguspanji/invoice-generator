---
paths:
  - 'resources/js/components/documentation/**'
---

# Components Documentation

## Tiptap v3 StarterKit sudah memuat Link
Jangan tambahkan @tiptap/extension-link terpisah saat memakai StarterKit v3: StarterKit sudah membundel Link, Underline, dan TrailingNode. Menambahkannya lagi memicu error runtime 'Duplicate extension names found: [\'link\']'. Placeholder tetap perlu @tiptap/extension-placeholder.

## Mermaid wajib dynamic import
Jangan import mermaid di top-level komponen: ia menarik elk/cytoscape ratusan KB ke bundle awal. DocumentPreviewModal.vue memuatnya via dynamic import() saat pratinjau dibuka, lalu mermaid.run dengan securityLevel strict pada node .mermaid:not([data-processed]).
