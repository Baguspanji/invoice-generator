<?php

namespace App\Services\Documentation;

/**
 * Mengubah JSON editor (Tiptap) menjadi HTML yang sudah di-escape.
 *
 * Semua teks di-escape dan URL disanitasi (hanya http/https/mailto/tel/relatif),
 * sehingga hasilnya aman untuk dirender maupun dicetak ke PDF.
 */
class HtmlRenderer
{
    private const MAX_DEPTH = 30;

    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * @param  array<string, mixed>|null  $content
     */
    public function toHtml(?array $content): string
    {
        if (! is_array($content) || ($content['type'] ?? null) !== 'doc') {
            return '';
        }

        return $this->renderChildren($content['content'] ?? [], 0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function renderChildren(array $nodes, int $depth): string
    {
        if ($depth > self::MAX_DEPTH) {
            return '';
        }

        $html = '';

        foreach ($nodes as $node) {
            if (is_array($node)) {
                $html .= $this->renderNode($node, $depth + 1);
            }
        }

        return $html;
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
            'paragraph' => '<p>'.$this->renderChildren($children, $depth).'</p>',
            'heading' => $this->renderHeading($children, $attrs, $depth),
            'bulletList' => '<ul>'.$this->renderChildren($children, $depth).'</ul>',
            'orderedList' => '<ol>'.$this->renderChildren($children, $depth).'</ol>',
            'listItem' => '<li>'.$this->renderChildren($children, $depth).'</li>',
            'taskList' => '<ul class="task-list">'.$this->renderChildren($children, $depth).'</ul>',
            'taskItem' => $this->renderTaskItem($children, $attrs, $depth),
            'blockquote' => '<blockquote>'.$this->renderChildren($children, $depth).'</blockquote>',
            'codeBlock' => $this->renderCodeBlock($children, $attrs),
            'horizontalRule' => '<hr>',
            'hardBreak' => '<br>',
            'image' => $this->renderImage($attrs),
            'table' => '<table>'.$this->renderChildren($children, $depth).'</table>',
            'tableRow' => '<tr>'.$this->renderChildren($children, $depth).'</tr>',
            'tableHeader' => '<th>'.$this->renderChildren($children, $depth).'</th>',
            'tableCell' => '<td>'.$this->renderChildren($children, $depth).'</td>',
            'callout' => $this->renderCallout($children, $attrs, $depth),
            default => $this->renderChildren($children, $depth),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @param  array<string, mixed>  $attrs
     */
    private function renderHeading(array $children, array $attrs, int $depth): string
    {
        $level = (int) ($attrs['level'] ?? 1);
        $level = max(1, min(6, $level));

        return sprintf('<h%1$d>%2$s</h%1$d>', $level, $this->renderChildren($children, $depth));
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @param  array<string, mixed>  $attrs
     */
    private function renderTaskItem(array $children, array $attrs, int $depth): string
    {
        $isChecked = (bool) ($attrs['checked'] ?? false);
        $marker = $isChecked ? '&#9745;' : '&#9744;';

        return '<li class="task-item"><span class="task-marker">'.$marker.'</span><span class="task-content" data-checked="'
            .($isChecked ? 'true' : 'false').'">'
            .$this->renderChildren($children, $depth).'</span></li>';
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @param  array<string, mixed>  $attrs
     */
    private function renderCodeBlock(array $children, array $attrs): string
    {
        $text = $this->plainText($children);
        $language = strtolower((string) ($attrs['language'] ?? ''));

        if ($language === 'mermaid') {
            return '<div class="mermaid">'.$this->escape($text).'</div>';
        }

        $class = $language !== '' ? ' class="language-'.$this->escape($language).'"' : '';

        return '<pre><code'.$class.'>'.$this->escape($text).'</code></pre>';
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @param  array<string, mixed>  $attrs
     */
    private function renderCallout(array $children, array $attrs, int $depth): string
    {
        $variant = $this->escape((string) ($attrs['variant'] ?? 'info'));

        return '<div class="callout callout-'.$variant.'">'.$this->renderChildren($children, $depth).'</div>';
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

        $alt = $this->escape((string) ($attrs['alt'] ?? ''));

        return '<img src="'.$this->escape($src).'" alt="'.$alt.'">';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderText(array $node): string
    {
        $text = $this->escape((string) ($node['text'] ?? ''));

        foreach ($node['marks'] ?? [] as $mark) {
            if (! is_array($mark)) {
                continue;
            }

            $type = $mark['type'] ?? '';
            $attrs = is_array($mark['attrs'] ?? null) ? $mark['attrs'] : [];

            $text = match ($type) {
                'bold' => '<strong>'.$text.'</strong>',
                'italic' => '<em>'.$text.'</em>',
                'strike' => '<s>'.$text.'</s>',
                'underline' => '<u>'.$text.'</u>',
                'code' => '<code>'.$text.'</code>',
                'link' => '<a href="'.$this->escape($this->safeUrl((string) ($attrs['href'] ?? ''))).'" rel="noopener noreferrer">'.$text.'</a>',
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

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (in_array($scheme, self::SAFE_SCHEMES, true)) {
            return $url;
        }

        return '';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
