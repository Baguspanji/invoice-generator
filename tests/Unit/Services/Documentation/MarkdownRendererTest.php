<?php

use App\Services\Documentation\MarkdownRenderer;

function rendererContent(array $nodes): array
{
    return ['type' => 'doc', 'content' => $nodes];
}

test('heading dan paragraf dikonversi ke markdown', function () {
    $markdown = (new MarkdownRenderer)->toMarkdown(rendererContent([
        ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Judul']]],
        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Isi paragraf.']]],
    ]));

    expect($markdown)->toBe("## Judul\n\nIsi paragraf.");
});

test('bullet list dan ordered list dikonversi ke markdown', function () {
    $renderer = new MarkdownRenderer;

    $bullet = $renderer->toMarkdown(rendererContent([
        ['type' => 'bulletList', 'content' => [
            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Satu']]]]],
            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Dua']]]]],
        ]],
    ]));

    $ordered = $renderer->toMarkdown(rendererContent([
        ['type' => 'orderedList', 'content' => [
            ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Pertama']]]]],
        ]],
    ]));

    expect($bullet)->toBe("- Satu\n- Dua");
    expect($ordered)->toBe('1. Pertama');
});

test('code block mempertahankan bahasa', function () {
    $markdown = (new MarkdownRenderer)->toMarkdown(rendererContent([
        ['type' => 'codeBlock', 'attrs' => ['language' => 'php'], 'content' => [['type' => 'text', 'text' => 'echo 1;']]],
    ]));

    expect($markdown)->toBe("```php\necho 1;\n```");
});

test('mark bold italic dan link dikonversi ke markdown', function () {
    $markdown = (new MarkdownRenderer)->toMarkdown(rendererContent([
        ['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'tebal', 'marks' => [['type' => 'bold']]],
            ['type' => 'text', 'text' => ' dan '],
            ['type' => 'text', 'text' => 'miring', 'marks' => [['type' => 'italic']]],
            ['type' => 'text', 'text' => ' '],
            ['type' => 'text', 'text' => 'tautan', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com']]]],
        ]],
    ]));

    expect($markdown)->toBe('**tebal** dan *miring* [tautan](https://example.com)');
});

test('tautan berbahaya dibuang saat konversi markdown', function () {
    $markdown = (new MarkdownRenderer)->toMarkdown(rendererContent([
        ['type' => 'paragraph', 'content' => [
            ['type' => 'text', 'text' => 'klik', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]],
        ]],
    ]));

    expect($markdown)->toBe('[klik]()');
});

test('task list menampilkan status selesai', function () {
    $markdown = (new MarkdownRenderer)->toMarkdown(rendererContent([
        ['type' => 'taskList', 'content' => [
            ['type' => 'taskItem', 'attrs' => ['checked' => true], 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Selesai']]]]],
            ['type' => 'taskItem', 'attrs' => ['checked' => false], 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Belum']]]]],
        ]],
    ]));

    expect($markdown)->toBe("- [x] Selesai\n- [ ] Belum");
});
