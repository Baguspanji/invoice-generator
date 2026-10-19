<?php

namespace App\Http\Controllers\Documentation;

use App\Enums\DocumentationPageStatus;
use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use App\Models\Project;
use App\Services\Documentation\PageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function show(Request $request, Project $project, PageService $pageService): View
    {
        $space = $project->documentationSpace()->firstOrCreate(
            [],
            [
                'name' => 'Dokumentasi '.$project->name,
                'created_by' => $request->user()->id,
            ],
        );

        $selectedPage = null;

        if ($request->filled('page')) {
            $selectedPage = $space->pages()
                ->with(['versions.creator', 'tasks', 'attachments'])
                ->find($request->integer('page'));
        }

        $selectedPage ??= $space->pages()
            ->with(['versions.creator', 'tasks', 'attachments'])
            ->orderBy('order_column')
            ->orderBy('id')
            ->first();

        $pagesBase = url('/documentation/pages');

        return view('documentation.workspace', [
            'project' => $project,
            'space' => $space,
            'config' => [
                'csrf' => csrf_token(),
                'pagesStore' => route('projects.documentation.pages.store', $project),
                'pagesBase' => $pagesBase,
                'workspaceUrl' => route('projects.documentation.show', $project),
                'search' => route('documentation.search'),
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'url' => route('projects.show', $project),
                ],
                'space' => [
                    'id' => $space->id,
                    'name' => $space->name,
                ],
                'categories' => $space->categories->map(fn ($category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])->values(),
                'tree' => $pageService->tree($space),
                'statuses' => collect(DocumentationPageStatus::cases())
                    ->map(fn (DocumentationPageStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->label(),
                        'badge_class' => $status->badgeClass(),
                    ])
                    ->values(),
                'projectTasks' => $project->tasks()
                    ->orderBy('order_column')
                    ->get(['id', 'title'])
                    ->map(fn ($task): array => ['id' => $task->id, 'title' => $task->title])
                    ->values(),
                'page' => $selectedPage ? $this->pagePayload($selectedPage) : null,
            ],
        ]);
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
            'category_id' => $page->category_id,
            'parent_id' => $page->parent_id,
            'lock_version' => $page->lock_version,
            'updated_at' => $page->updated_at?->toISOString(),
            'updated_at_label' => $page->updated_at?->translatedFormat('d M Y H:i'),
            'status' => [
                'value' => $page->status->value,
                'label' => $page->status->label(),
                'badge_class' => $page->status->badgeClass(),
            ],
            'versions' => $page->versions->map(fn ($version): array => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'title' => $version->title,
                'change_summary' => $version->change_summary,
                'status' => $version->status->label(),
                'created_at' => $version->created_at?->toISOString(),
                'created_at_label' => $version->created_at?->translatedFormat('d M Y H:i'),
                'creator_name' => $version->creator?->name,
            ])->values(),
            'tasks' => $page->tasks->map(fn ($task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => [
                    'value' => $task->priority->value,
                    'label' => $task->priority->label(),
                    'badge_class' => $task->priority->badgeClass(),
                ],
            ])->values(),
            'attachments' => $page->attachments->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_size' => $attachment->file_size,
                'human_size' => $attachment->humanSize(),
                'mime_type' => $attachment->mime_type,
                'url' => $attachment->url(),
            ])->values(),
        ];
    }
}
