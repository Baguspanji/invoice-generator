<?php

namespace App\Mcp\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Partially update a task. Only the provided fields are changed. Use completed true/false to mark done or reopen.')]
class UpdateTask extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'task_id' => ['required', 'integer', 'min:1'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'task_group_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'priority' => ['sometimes', 'nullable', 'in:low,medium,high,urgent'],
            'estimation' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'fixed_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'assigned_to_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'billable' => ['sometimes', 'nullable', 'boolean'],
            'completed' => ['sometimes', 'nullable', 'boolean'],
            'label_ids' => ['sometimes', 'nullable', 'array'],
            'label_ids.*' => ['integer', 'exists:labels,id'],
        ]);

        try {
            $task = Task::findOrFail($request->get('task_id'));
        } catch (ModelNotFoundException) {
            return Response::error('Task not found.');
        }

        $data = [];

        foreach (['title', 'description', 'priority', 'estimation', 'fixed_price', 'assigned_to_user_id'] as $field) {
            if ($request->get($field) !== null) {
                $data[$field] = $request->get($field);
            }
        }

        if ($request->get('task_group_id') !== null && ! $task->isSubtask()) {
            $group = $task->project->taskGroups()->find($request->get('task_group_id'));

            if (! $group) {
                return Response::error('Task group not found in this task project.');
            }

            $data['task_group_id'] = $group->id;
            $task->subtasks()->update(['task_group_id' => $group->id]);
        }

        if ($request->get('billable') !== null) {
            $data['billable'] = (bool) $request->get('billable');
        }

        if ($request->get('completed') !== null) {
            if ($request->get('completed') && $task->hasOpenSubtasks()) {
                return Response::error('Task cannot be completed while it has open subtasks.');
            }

            $data['completed_at'] = $request->get('completed') ? ($task->completed_at ?? now()) : null;
        }

        if ($data !== []) {
            $task->update($data);
        }

        if ($request->get('label_ids') !== null) {
            $task->labels()->sync($request->get('label_ids') ?? []);
        }

        $task->refresh();

        return Response::json([
            'id' => $task->id,
            'project_id' => $task->project_id,
            'task_group_id' => $task->task_group_id,
            'title' => $task->title,
            'priority' => $task->priority->value,
            'completed' => $task->completed_at !== null,
            'billable' => (bool) $task->billable,
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
            'task_id' => $schema->integer()->description('ID of the task')->required()->min(1),
            'title' => $schema->string()->description('New task title')->max(255),
            'description' => $schema->string()->description('New task description'),
            'task_group_id' => $schema->integer()->description('Move to column ID (must belong to the same project)')->min(1),
            'priority' => $schema->string()->description('One of: low, medium, high, urgent'),
            'estimation' => $schema->number()->description('Estimation in hours')->min(0),
            'fixed_price' => $schema->number()->description('Fixed price in IDR')->min(0),
            'assigned_to_user_id' => $schema->integer()->description('ID of the assignee')->min(1),
            'billable' => $schema->boolean()->description('Billable to invoice'),
            'completed' => $schema->boolean()->description('True to mark done, false to reopen'),
            'label_ids' => $schema->array()->description('Replace attached labels (empty array clears all)')->items($schema->integer()),
        ];
    }
}
