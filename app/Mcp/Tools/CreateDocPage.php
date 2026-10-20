<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\MarkdownToDoc;
use App\Mcp\Support\McpActor;
use App\Models\Project;
use App\Services\Documentation\PageService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a documentation page (status draft) in a project workspace. Content is Markdown (headings, paragraphs, lists, code fences). The workspace is created automatically if missing.')]
class CreateDocPage extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, PageService $pages): Response
    {
        $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'content_markdown' => ['sometimes', 'nullable', 'string'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        try {
            $project = Project::findOrFail($request->get('project_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        }

        $actor = McpActor::resolve();

        if (! $actor) {
            return Response::error('No staff user available to attribute this change to.');
        }

        $space = $project->documentationSpace()->firstOrCreate([], [
            'name' => 'Dokumentasi '.$project->name,
            'created_by' => $actor->id,
        ]);

        $categoryId = $request->get('category_id');

        if ($categoryId !== null && ! $space->categories()->whereKey($categoryId)->exists()) {
            return Response::error('Category not found in this project workspace.');
        }

        $data = [
            'title' => $request->get('title'),
            'parent_id' => $request->get('parent_id'),
            'category_id' => $categoryId,
        ];

        if ($request->get('content_markdown') !== null) {
            $data['content'] = MarkdownToDoc::convert($request->get('content_markdown'));
        }

        try {
            $page = $pages->create($space, $data, $actor);
        } catch (ValidationException $exception) {
            return Response::error(collect($exception->errors())->flatten()->implode(' '));
        }

        return Response::json([
            'id' => $page->id,
            'project_id' => $project->id,
            'title' => $page->title,
            'status' => $page->status->value,
            'lock_version' => $page->lock_version,
        ]);
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
            'title' => $schema->string()->description('Page title')->required()->max(255),
            'content_markdown' => $schema->string()->description('Page content in Markdown'),
            'parent_id' => $schema->integer()->description('Parent page ID (same project)')->min(1),
            'category_id' => $schema->integer()->description('Category ID (same project workspace)')->min(1),
        ];
    }
}
