<?php

namespace App\Services\Documentation;

/**
 * Mengubah JSON editor (Tiptap) menjadi Markdown.
 */
class MarkdownRenderer
{
    private const MAX_DEPTH = 30;

    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * @param  array<string, mixed>|null  $content
     */
    public function toMarkdown(?array $content): string
    {
        if (! is_array($content) || ($content['type'] ?? null) !== 'doc') {
            return '';
        }

        return trim($this->renderNodes($content['content'] ?? [], 0));
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function renderNodes(array $nodes, int $depth): string
    {
        if ($depth > self::MAX_DEPTH) {
            return '';
        }

        $markdown = '';

        foreach ($nodes as $node) {
            if (is_array($node)) {
                $markdown .= $this->renderNode($node, $depth + 1);
            }
        }

        return $markdown;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderNode(array $node, int $depth): string
    {
        $type = $node['type'] ?? '';
        $children = is_array($node['content'] ?? null) ? $node['content'] : [];
        $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];

        return match ($type) {
            'text' => $this->renderText($node),
            'paragraph' => $this->renderInline($children, $depth)."\n\n",
            'heading' => str_repeat('#', max(1, min(6, (int) ($attrs['level'] ?? 1))))
                .' '.$this->renderInline($children, $depth)."\n\n",
            'bulletList' => $this->renderList($children, $depth, false),
            'orderedList' => $this->renderList($children, $depth, true),
            'taskList' => $this->renderTaskList($children, $depth),
            'blockquote' => $this->renderBlockquote($children, $depth),
            'codeBlock' => $this->renderCodeBlock($children, $attrs),
            'horizontalRule' => "---\n\n",
            'hardBreak' => "  \n",
            'image' => $this->renderImage($attrs)."\n\n",
            'table' => $this->renderTable($children, $depth),
            'callout' => '> '.trim($this->renderInline($children, $depth))."\n\n",
            default => $this->renderInline($children, $depth),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function renderInline(array $nodes, int $depth): string
    {
        return $this->renderNodes($nodes, $depth);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function renderList(array $items, int $depth, bool $ordered): string
    {
        $indent = str_repeat('  ', max(0, $depth - 1));
        $markdown = '';
        $index = 1;

        foreach ($items as $item) {
            if (! is_array($item) || ($item['type'] ?? '') !== 'listItem') {
                continue;
            }

            $prefix = $ordered ? ($index++).'. ' : '- ';
            $markdown .= $indent.$prefix.$this->renderListItem($item['content'] ?? [], $depth)."\n";
        }

        return $markdown."\n";
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function renderTaskList(array $items, int $depth): string
    {
        $indent = str_repeat('  ', max(0, $depth - 1));
        $markdown = '';

        foreach ($items as $item) {
            if (! is_array($item) || ($item['type'] ?? '') !== 'taskItem') {
                continue;
            }

            $checked = ($item['attrs']['checked'] ?? false) ? 'x' : ' ';
            $markdown .= $indent.'- ['.$checked.'] '.$this->renderListItem($item['content'] ?? [], $depth)."\n";
        }

        return $markdown."\n";
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function renderListItem(array $nodes, int $depth): string
    {
        $text = '';
        $rest = '';

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type = $node['type'] ?? '';

            if ($type === 'paragraph') {
                $inline = trim($this->renderInline($node['content'] ?? [], $depth));
                $text = $text === '' ? $inline : $text.' '.$inline;
            } elseif ($type === 'bulletList' || $type === 'orderedList') {
                $rest .= "\n".$this->renderList($node['content'] ?? [], $depth + 1, $type === 'orderedList');
            } else {
                $rest .= $this->renderNode($node, $depth + 1);
            }
        }

        return $text.$rest;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function renderBlockquote(array $nodes, int $depth): string
    {
        $inner = trim($this->renderNodes($nodes, $depth));
        $lines = explode("\n", $inner);

        return implode("\n", array_map(fn (string $line): string => '> '.$line, $lines))."\n\n";
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @param  array<string, mixed>  $attrs
     */
    private function renderCodeBlock(array $children, array $attrs): string
    {
        $language = strtolower((string) ($attrs['language'] ?? ''));
        $code = $this->plainText($children);

        return '```'.$language."\n".$code."\n```\n\n";
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function renderImage(array $attrs): string
    {
        $src = $this->safeUrl((string) ($attrs['src'] ?? ''));

        if ($src === '') {
            return '';
        }

        return '!['.($attrs['alt'] ?? '').']('.$src.')';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function renderTable(array $rows, int $depth): string
    {
        $header = [];
        $body = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ($row['type'] ?? '') !== 'tableRow') {
                continue;
            }

            $cells = [];
            $isHeaderRow = false;

            foreach ($row['content'] ?? [] as $cell) {
                if (! is_array($cell)) {
                    continue;
                }

                $isHeaderRow = $isHeaderRow || ($cell['type'] ?? '') === 'tableHeader';
                $cells[] = str_replace("\n", ' ', trim($this->renderInline($cell['content'] ?? [], $depth)));
            }

            if ($header === [] && $isHeaderRow) {
                $header = $cells;
            } else {
                $body[] = $cells;
            }
        }

        if ($header === []) {
            $header = array_shift($body) ?? [];
        }

        if ($header === []) {
            return '';
        }

        $markdown = '| '.implode(' | ', $header)." |\n";
        $markdown .= '| '.implode(' | ', array_fill(0, count($header), '---'))." |\n";

        foreach ($body as $cells) {
            $markdown .= '| '.implode(' | ', $cells)." |\n";
        }

        return $markdown."\n";
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderText(array $node): string
    {
        $text = (string) ($node['text'] ?? '');

        foreach ($node['marks'] ?? [] as $mark) {
            if (! is_array($mark)) {
                continue;
            }

            $type = $mark['type'] ?? '';
            $attrs = is_array($mark['attrs'] ?? null) ? $mark['attrs'] : [];

            $text = match ($type) {
                'bold' => '**'.$text.'**',
                'italic' => '*'.$text.'*',
                'strike' => '~~'.$text.'~~',
                'code' => '`'.str_replace('`', '\\`', $text).'`',
                'link' => '['.$text.']('.$this->safeUrl((string) ($attrs['href'] ?? '')).')',
                default => $text,
            };
        }

        return $text;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function plainText(array $nodes): string
    {
        $text = '';

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['type'] ?? '') === 'text') {
                $text .= (string) ($node['text'] ?? '');
            }

            if (is_array($node['content'] ?? null)) {
                $text .= $this->plainText($node['content']);
            }
        }

        return $text;
    }

    private function safeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, self::SAFE_SCHEMES, true) ? $url : '';
    }
}
