<?php

namespace App\Mcp\Tools;

use App\Models\DocumentationPage;
use App\Models\Task;
use App\Services\Documentation\MarkdownRenderer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Show a documentation page with its content as Markdown, status, lock_version (needed for update-doc-page), linked tasks and recent versions.')]
#[IsReadOnly]
class DocPageDetail extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, MarkdownRenderer $markdown): Response
    {
        $request->validate([
            'page_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $page = DocumentationPage::with(['space', 'category', 'tasks', 'versions'])->findOrFail($request->get('page_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Documentation page not found.');
        }

        return Response::json([
            'id' => $page->id,
            'project_id' => $page->space->project_id,
            'title' => $page->title,
            'category' => $page->category?->name,
            'parent_id' => $page->parent_id,
            'status' => $page->status->value,
            'lock_version' => $page->lock_version,
            'published_at' => $page->published_at?->toISOString(),
            'updated_at' => $page->updated_at?->toISOString(),
            'content_markdown' => $markdown->toMarkdown($page->content),
            'tasks' => $page->tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
            ])->values()->all(),
            'versions' => $page->versions->take(10)->map(fn ($version) => [
                'version_number' => $version->version_number,
                'change_summary' => $version->change_summary,
                'created_at' => $version->created_at?->toISOString(),
            ])->values()->all(),
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
            'page_id' => $schema->integer()->description('ID of the documentation page')->required()->min(1),
        ];
    }
}
