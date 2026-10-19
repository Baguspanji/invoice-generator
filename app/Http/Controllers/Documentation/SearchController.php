<?php

namespace App\Http\Controllers\Documentation;

use App\Enums\DocumentationPageStatus;
use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'category_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(DocumentationPageStatus::class)],
        ]);

        $term = trim($validated['q'] ?? '');
        $categoryId = $validated['category_id'] ?? null;
        $status = $validated['status'] ?? null;

        if ($term === '' && ! $categoryId && ! $status) {
            return response()->json(['results' => []]);
        }

        $pages = DocumentationPage::query()
            ->with('space')
            ->when(
                $request->filled('project_id'),
                fn ($query) => $query->whereHas(
                    'space',
                    fn ($spaceQuery) => $spaceQuery->where('project_id', $request->integer('project_id')),
                ),
            )
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->where(function ($inner) use ($term) {
                $inner->where('title', 'like', '%'.$term.'%')
                    ->orWhere('content', 'like', '%'.$term.'%');
            }))
            ->orderBy('title')
            ->limit(20)
            ->get();

        $results = $pages->map(fn (DocumentationPage $page): array => [
            'id' => $page->id,
            'title' => $page->title,
            'category_id' => $page->category_id,
            'status' => [
                'value' => $page->status->value,
                'label' => $page->status->label(),
            ],
            'excerpt' => $this->excerpt($page, $term),
            'project_id' => $page->space?->project_id,
            'url' => $page->space
                ? route('projects.documentation.show', ['project' => $page->space->project_id, 'page' => $page->id])
                : null,
        ])->values();

        return response()->json(['results' => $results]);
    }

    private function excerpt(DocumentationPage $page, string $term): ?string
    {
        if ($term === '') {
            return null;
        }

        $content = $page->content ?? [];
        $text = $this->plainText($content['content'] ?? []);

        if ($text === '') {
            return null;
        }

        $position = mb_stripos($text, $term);

        if ($position === false) {
            return null;
        }

        $start = max(0, $position - 60);
        $snippet = mb_substr($text, $start, 160);

        return trim(($start > 0 ? '…' : '').$snippet.'…');
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
                $text .= (string) ($node['text'] ?? '').' ';
            }

            if (is_array($node['content'] ?? null)) {
                $text .= $this->plainText($node['content']);
            }
        }

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
