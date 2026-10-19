<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        $term = trim($validated['q'] ?? '');

        if ($term === '') {
            return response()->json(['results' => []]);
        }

        $results = DocumentationPage::query()
            ->with('space')
            ->when(
                $request->filled('project_id'),
                fn ($query) => $query->whereHas(
                    'space',
                    fn ($spaceQuery) => $spaceQuery->where('project_id', $request->integer('project_id')),
                ),
            )
            ->where('title', 'like', '%'.$term.'%')
            ->orderBy('title')
            ->limit(20)
            ->get()
            ->map(fn (DocumentationPage $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'project_id' => $page->space?->project_id,
                'url' => $page->space
                    ? route('projects.documentation.show', ['project' => $page->space->project_id, 'page' => $page->id])
                    : null,
            ])
            ->values();

        return response()->json(['results' => $results]);
    }
}
