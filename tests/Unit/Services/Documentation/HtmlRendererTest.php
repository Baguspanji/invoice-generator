<?php

use App\Services\Documentation\HtmlRenderer;

test('teks di-escape saat dirender ke html', function () {
    $html = (new HtmlRenderer)->toHtml([
        'type' => 'doc',
        'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '<b>tebal</b>']]],
        ],
    ]);

    expect($html)->toBe('<p>&lt;b&gt;tebal&lt;/b&gt;</p>');
});

test('heading dirender sesuai level', function () {
    $html = (new HtmlRenderer)->toHtml([
        'type' => 'doc',
        'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Judul']]],
        ],
    ]);

    expect($html)->toBe('<h2>Judul</h2>');
});

test('tautan berbahaya dibuang saat dirender ke html', function () {
    $html = (new HtmlRenderer)->toHtml([
        'type' => 'doc',
        'content' => [
            ['type' => 'paragraph', 'content' => [
                ['type' => 'text', 'text' => 'klik', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]],
            ]],
        ],
    ]);

    expect($html)->toBe('<p><a href="" rel="noopener noreferrer">klik</a></p>');
});

test('code block mermaid dirender sebagai div mermaid', function () {
    $html = (new HtmlRenderer)->toHtml([
        'type' => 'doc',
        'content' => [
            ['type' => 'codeBlock', 'attrs' => ['language' => 'mermaid'], 'content' => [['type' => 'text', 'text' => 'graph TD; A-->B;']]],
        ],
    ]);

    expect($html)->toBe('<div class="mermaid">graph TD; A--&gt;B;</div>');
});

test('code block biasa dirender sebagai pre code', function () {
    $html = (new HtmlRenderer)->toHtml([
        'type' => 'doc',
        'content' => [
            ['type' => 'codeBlock', 'attrs' => ['language' => 'php'], 'content' => [['type' => 'text', 'text' => 'echo 1;']]],
        ],
    ]);

    expect($html)->toBe('<pre><code class="language-php">echo 1;</code></pre>');
});

test('tabel dan gambar dirender ke html', function () {
    $html = (new HtmlRenderer)->toHtml([
        'type' => 'doc',
        'content' => [
            ['type' => 'table', 'content' => [
                ['type' => 'tableRow', 'content' => [
                    ['type' => 'tableHeader', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Kolom']]]]],
                ]],
            ]],
            ['type' => 'image', 'attrs' => ['src' => 'https://example.com/a.png', 'alt' => 'gambar']],
        ],
    ]);

    expect($html)
        ->toContain('<table>')
        ->toContain('<th>')
        ->toContain('<img src="https://example.com/a.png" alt="gambar">');
});

test('gambar dengan src berbahaya dibuang', function () {
    $html = (new HtmlRenderer)->toHtml([
        'type' => 'doc',
        'content' => [
            ['type' => 'image', 'attrs' => ['src' => 'javascript:alert(1)']],
        ],
    ]);

    expect($html)->toBe('');
});
