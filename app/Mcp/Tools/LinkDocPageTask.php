<?php

namespace App\Mcp\Tools;

use App\Enums\DocumentationActivityAction;
use App\Mcp\Support\McpActor;
use App\Models\DocumentationPage;
use App\Models\Task;
use App\Services\Documentation\ActivityLogger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Link or unlink a task to a documentation page. The task must belong to the same project as the page workspace.')]
class LinkDocPageTask extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ActivityLogger $activity): Response
    {
        $request->validate([
            'page_id' => ['required', 'integer', 'min:1'],
            'task_id' => ['required', 'integer', 'min:1'],
            'unlink' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $page = DocumentationPage::with('space')->find($request->get('page_id'));
        $task = Task::find($request->get('task_id'));

        if (! $page) {
            return Response::error('Documentation page not found.');
        }

        if (! $task) {
            return Response::error('Task not found.');
        }

        if ((int) $task->project_id !== (int) $page->space->project_id) {
            return Response::error('Task must belong to the same project as the documentation workspace.');
        }

        $actor = McpActor::resolve();

        if ($request->get('unlink')) {
            $page->tasks()->detach($task->id);
            $activity->log($page, $actor, DocumentationActivityAction::TASK_UNLINKED, 'Melepas tautan task «'.$task->title.'»', ['task_id' => $task->id]);
        } else {
            if (! $actor) {
                return Response::error('No staff user available to attribute this change to.');
            }

            $page->tasks()->syncWithoutDetaching([$task->id => ['created_by' => $actor->id]]);
            $activity->log($page, $actor, DocumentationActivityAction::TASK_LINKED, 'Menautkan task «'.$task->title.'»', ['task_id' => $task->id]);
        }

        return Response::json([
            'page_id' => $page->id,
            'tasks' => $page->tasks()->get()->map(fn (Task $linked) => [
                'id' => $linked->id,
                'title' => $linked->title,
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
            'task_id' => $schema->integer()->description('ID of the task')->required()->min(1),
            'unlink' => $schema->boolean()->description('True to remove the link instead of adding it'),
        ];
    }
}
