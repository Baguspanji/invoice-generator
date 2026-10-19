<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use App\Services\Documentation\HtmlRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreviewController extends Controller
{
    public function show(Request $request, DocumentationPage $page, HtmlRenderer $renderer): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['nullable', 'array'],
        ]);

        $content = $validated['content'] ?? $page->content;

        return response()->json([
            'html' => $renderer->toHtml($content),
            'title' => $page->title,
        ]);
    }
}
