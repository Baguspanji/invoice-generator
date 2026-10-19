<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use App\Models\Project;
use App\Services\Documentation\PageService;
use App\Services\Documentation\PublishingService;
use App\Services\Documentation\StaleContentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function store(Request $request, Project $project, PageService $pageService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $space = $project->documentationSpace()->firstOrCreate(
            [],
            [
                'name' => 'Dokumentasi '.$project->name,
                'created_by' => $request->user()->id,
            ],
        );

        $page = $pageService->create($space, $validated, $request->user());

        return redirect()
            ->route('projects.documentation.show', ['project' => $project, 'page' => $page->id])
            ->with('success', 'Halaman dokumentasi berhasil dibuat.');
    }

    public function update(Request $request, DocumentationPage $page, PageService $pageService): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'array'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'parent_id' => ['sometimes', 'nullable', 'integer'],
            'order_column' => ['sometimes', 'integer', 'min:0'],
            'lock_version' => ['sometimes', 'integer', 'min:1'],
            'change_summary' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        try {
            $page = $pageService->update($page, $validated, $request->user());
        } catch (StaleContentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'lock_version' => $exception->currentLockVersion,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'page' => $this->pagePayload($page),
        ]);
    }

    public function destroy(Request $request, DocumentationPage $page, PageService $pageService): RedirectResponse
    {
        $project = $page->space->project;

        $pageService->delete($page, $request->user());

        return redirect()
            ->route('projects.documentation.show', $project)
            ->with('success', 'Halaman dokumentasi berhasil dihapus.');
    }

    public function publish(Request $request, DocumentationPage $page, PublishingService $publishingService): JsonResponse
    {
        $validated = $request->validate([
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);

        $page = $publishingService->publish($page, $request->user(), $validated['change_summary'] ?? null);

        return response()->json(['success' => true, 'page' => $this->pagePayload($page)]);
    }

    public function submitReview(Request $request, DocumentationPage $page, PublishingService $publishingService): JsonResponse
    {
        $page = $publishingService->submitForReview($page, $request->user());

        return response()->json(['success' => true, 'page' => $this->pagePayload($page)]);
    }

    public function requestChanges(Request $request, DocumentationPage $page, PublishingService $publishingService): JsonResponse
    {
        $page = $publishingService->requestChanges($page, $request->user());

        return response()->json(['success' => true, 'page' => $this->pagePayload($page)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pagePayload(DocumentationPage $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'lock_version' => $page->lock_version,
            'updated_at' => $page->updated_at?->toISOString(),
            'updated_at_label' => $page->updated_at?->translatedFormat('d M Y H:i'),
            'status' => [
                'value' => $page->status->value,
                'label' => $page->status->label(),
                'badge_class' => $page->status->badgeClass(),
            ],
        ];
    }
}
