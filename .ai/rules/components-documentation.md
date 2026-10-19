---
paths:
  - 'resources/js/components/documentation/**'
---

# Components Documentation

## Tiptap v3 StarterKit sudah memuat Link
Jangan tambahkan @tiptap/extension-link terpisah saat memakai StarterKit v3: StarterKit sudah membundel Link, Underline, dan TrailingNode. Menambahkannya lagi memicu error runtime 'Duplicate extension names found: [\'link\']'. Placeholder tetap perlu @tiptap/extension-placeholder.
