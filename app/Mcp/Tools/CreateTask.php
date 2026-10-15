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

#[Description('Create a new task in a project column. The column must belong to the project.')]
class CreateTask extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'task_group_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'nullable', 'in:low,medium,high,urgent'],
            'estimation' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'fixed_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'assigned_to_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'billable' => ['sometimes', 'nullable', 'boolean'],
            'label_ids' => ['sometimes', 'nullable', 'array'],
            'label_ids.*' => ['integer', 'exists:labels,id'],
        ]);

        try {
            $project = Project::findOrFail($request->get('project_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        }

        $group = $project->taskGroups()->find($request->get('task_group_id'));

        if (! $group) {
            return Response::error('Task group not found in this project.');
        }

        $maxOrder = $project->tasks()->where('task_group_id', $group->id)->max('order_column') ?? 0;

        $task = $project->tasks()->create([
            'task_group_id' => $group->id,
            'title' => $request->get('title'),
            'priority' => $request->get('priority') ?? 'medium',
            'description' => $request->get('description'),
            'assigned_to_user_id' => $request->get('assigned_to_user_id'),
            'estimation' => $request->get('estimation'),
            'fixed_price' => $request->get('fixed_price'),
            'billable' => (bool) ($request->get('billable') ?? false),
            'order_column' => $maxOrder + 1,
        ]);

        if ($request->get('label_ids')) {
            $task->labels()->sync($request->get('label_ids'));
        }

        return Response::json([
            'id' => $task->id,
            'project_id' => $project->id,
            'task_group_id' => $group->id,
            'title' => $task->title,
            'priority' => $task->priority->value,
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
            'task_group_id' => $schema->integer()->description('ID of the target column (must belong to the project)')->required()->min(1),
            'title' => $schema->string()->description('Task title')->required()->max(255),
            'description' => $schema->string()->description('Task description'),
            'priority' => $schema->string()->description('One of: low, medium, high, urgent'),
            'estimation' => $schema->number()->description('Estimation in hours')->min(0),
            'fixed_price' => $schema->number()->description('Fixed price in IDR')->min(0),
            'assigned_to_user_id' => $schema->integer()->description('ID of the assignee')->min(1),
            'billable' => $schema->boolean()->description('Billable to invoice (default false)'),
            'label_ids' => $schema->array()->description('Label IDs to attach')->items($schema->integer()),
        ];
    }
}
