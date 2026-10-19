<?php

namespace App\Services\Documentation;

use Illuminate\Validation\ValidationException;

/**
 * Memvalidasi struktur JSON editor (Tiptap) sebelum disimpan.
 *
 * Membatasi jenis node, kedalaman, dan jumlah node agar konten
 * yang tersimpan tetap terstruktur dan tidak membebani renderer.
 */
class ContentValidationService
{
    private const MAX_DEPTH = 20;

    private const MAX_NODES = 2000;

    /**
     * Whitelist node yang diizinkan. Sesuaikan bila extension editor berubah.
     *
     * @var list<string>
     */
    private const ALLOWED_NODES = [
        'doc',
        'paragraph',
        'text',
        'heading',
        'bulletList',
        'orderedList',
        'listItem',
        'taskList',
        'taskItem',
        'blockquote',
        'codeBlock',
        'horizontalRule',
        'hardBreak',
        'table',
        'tableRow',
        'tableHeader',
        'tableCell',
        'image',
        'callout',
    ];

    /**
     * @param  array<string, mixed>|null  $content
     *
     * @throws ValidationException
     */
    public function validate(?array $content): void
    {
        if ($content === null) {
            return;
        }

        if (($content['type'] ?? null) !== 'doc') {
            throw ValidationException::withMessages([
                'content' => 'Konten harus berupa dokumen editor yang valid.',
            ]);
        }

        $count = 0;
        $this->walk($content, 1, $count);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function walk(array $node, int $depth, int &$count): void
    {
        $count++;

        if ($count > self::MAX_NODES) {
            throw ValidationException::withMessages([
                'content' => 'Konten terlalu besar (melebihi '.self::MAX_NODES.' node).',
            ]);
        }

        if ($depth > self::MAX_DEPTH) {
            throw ValidationException::withMessages([
                'content' => 'Struktur konten terlalu dalam.',
            ]);
        }

        $type = $node['type'] ?? null;

        if (! is_string($type) || ! in_array($type, self::ALLOWED_NODES, true)) {
            throw ValidationException::withMessages([
                'content' => 'Jenis blok tidak didukung: '.($type ?? 'unknown').'.',
            ]);
        }

        $children = $node['content'] ?? [];

        if (! is_array($children)) {
            return;
        }

        foreach ($children as $child) {
            if (is_array($child)) {
                $this->walk($child, $depth + 1, $count);
            }
        }
    }
}
