<?php

namespace App\Mcp\Support;

/**
 * Mengubah Markdown sederhana (heading, paragraf, list, blok kode) menjadi JSON Tiptap.
 */
class MarkdownToDoc
{
    /**
     * @return array<string, mixed>
     */
    public static function convert(string $markdown): array
    {
        $blocks = [];
        $lines = preg_split('/\R/', $markdown) ?: [];
        $paragraph = [];
        $list = null;
        $code = null;

        $flushParagraph = function () use (&$blocks, &$paragraph): void {
            if ($paragraph !== []) {
                $blocks[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => implode(' ', $paragraph)]]];
                $paragraph = [];
            }
        };
        $flushList = function () use (&$blocks, &$list): void {
            if ($list !== null) {
                $blocks[] = $list;
                $list = null;
            }
        };

        foreach ($lines as $line) {
            if ($code !== null) {
                if (str_starts_with(trim($line), '```')) {
                    $blocks[] = [
                        'type' => 'codeBlock',
                        'attrs' => ['language' => $code['language']],
                        'content' => $code['lines'] === [] ? [] : [['type' => 'text', 'text' => implode("\n", $code['lines'])]],
                    ];
                    $code = null;
                } else {
                    $code['lines'][] = $line;
                }

                continue;
            }

            if (preg_match('/^```(\w*)/', trim($line), $match)) {
                $flushParagraph();
                $flushList();
                $code = ['language' => $match[1] ?: null, 'lines' => []];

                continue;
            }

            if (trim($line) === '') {
                $flushParagraph();
                $flushList();

                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.+)$/', $line, $match)) {
                $flushParagraph();
                $flushList();
                $blocks[] = [
                    'type' => 'heading',
                    'attrs' => ['level' => strlen($match[1])],
                    'content' => [['type' => 'text', 'text' => trim($match[2])]],
                ];

                continue;
            }

            if (preg_match('/^\s*(?:([-*])|(\d+)\.)\s+(.+)$/', $line, $match)) {
                $flushParagraph();
                $type = $match[1] !== '' ? 'bulletList' : 'orderedList';

                if ($list !== null && $list['type'] !== $type) {
                    $flushList();
                }

                $list ??= ['type' => $type, 'content' => []];
                $list['content'][] = [
                    'type' => 'listItem',
                    'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => trim($match[3])]]]],
                ];

                continue;
            }

            $flushList();
            $paragraph[] = trim($line);
        }

        if ($code !== null) {
            $blocks[] = [
                'type' => 'codeBlock',
                'attrs' => ['language' => $code['language']],
                'content' => $code['lines'] === [] ? [] : [['type' => 'text', 'text' => implode("\n", $code['lines'])]],
            ];
        }

        $flushParagraph();
        $flushList();

        return ['type' => 'doc', 'content' => $blocks];
    }
}
