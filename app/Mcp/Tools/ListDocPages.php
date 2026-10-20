<?php

namespace App\Mcp\Tools;

use App\Enums\DocumentationPageStatus;
use App\Models\DocumentationPage;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List documentation pages of a project (title, status, parent, category). Optionally search by title/content or filter by status.')]
#[IsReadOnly]
class ListDocPages extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', Rule::enum(DocumentationPageStatus::class)],
        ]);

        try {
            $project = Project::findOrFail($request->get('project_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        }

        $space = $project->documentationSpace;

        if (! $space) {
            return Response::json(['project_id' => $project->id, 'pages' => []]);
        }

        $search = trim((string) $request->get('search'));

        $pages = $space->pages()
            ->when($request->get('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%");
            }))
            ->orderBy('order_column')
            ->orderBy('id')
            ->get()
            ->map(fn (DocumentationPage $page) => [
                'id' => $page->id,
                'title' => $page->title,
                'parent_id' => $page->parent_id,
                'category_id' => $page->category_id,
                'status' => $page->status->value,
                'lock_version' => $page->lock_version,
                'updated_at' => $page->updated_at?->toISOString(),
            ])
            ->values()
            ->all();

        return Response::json(['project_id' => $project->id, 'pages' => $pages]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('ID of the project')->required()->min(1),
            'search' => $schema->string()->description('Search in title and content')->max(100),
            'status' => $schema->string()->description('One of: draft, in_review, changes_requested, published'),
        ];
    }
}
