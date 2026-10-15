<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Show a project kanban board: task groups with their tasks, priorities, assignees and completion state.')]
#[IsReadOnly]
class ProjectBoard extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $project = Project::with([
                'customer',
                'taskGroups.tasks' => fn ($query) => $query->with(['assignedUser', 'labels'])->orderBy('order_column'),
            ])->findOrFail($request->get('project_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        }

        return Response::json([
            'id' => $project->id,
            'name' => $project->name,
            'customer' => $project->customer?->name,
            'groups' => $project->taskGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'tasks' => $group->tasks->map(fn ($task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority->value,
                    'completed' => $task->completed_at !== null,
                    'assignee' => $task->assignedUser?->name,
                    'labels' => $task->labels->pluck('name')->values()->all(),
                ])->values()->all(),
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
            'project_id' => $schema->integer()->description('ID of the project')->required()->min(1),
        ];
    }
}
