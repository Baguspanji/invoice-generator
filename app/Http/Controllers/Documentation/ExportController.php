<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use App\Services\Documentation\HtmlRenderer;
use App\Services\Documentation\MarkdownRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function markdown(DocumentationPage $page, MarkdownRenderer $renderer): Response
    {
        $body = '# '.$page->title."\n\n".$renderer->toMarkdown($page->content)."\n";

        return response($body, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($page, 'md').'"',
        ]);
    }

    public function pdf(DocumentationPage $page, HtmlRenderer $renderer): Response
    {
        $html = view('documentation.pdf.page', [
            'page' => $page,
            'space' => $page->space,
            'project' => $page->space?->project,
            'contentHtml' => $renderer->toHtml($page->content),
            'generatedAt' => now(),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->download($this->filename($page, 'pdf'));
    }

    private function filename(DocumentationPage $page, string $extension): string
    {
        $slug = Str::slug($page->title) ?: 'dokumentasi';

        return $slug.'.'.$extension;
    }
}
