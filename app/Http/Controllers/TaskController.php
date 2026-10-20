<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'task_group_id' => ['required_without:parent_id', 'nullable', 'exists:task_groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'description' => ['nullable', 'string'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'estimation' => ['nullable', 'numeric', 'min:0'],
            'fixed_price' => ['nullable', 'numeric', 'min:0'],
            'billable' => ['nullable', 'boolean'],
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => ['exists:labels,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $parent = null;
        if (! empty($validated['parent_id'])) {
            $parent = $project->tasks()->topLevel()->find($validated['parent_id']);

            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent task harus berupa task utama dalam project ini.',
                ]);
            }
        }

        $taskGroupId = $parent?->task_group_id ?? $validated['task_group_id'];

        $maxOrder = $project->tasks()
            ->where('task_group_id', $taskGroupId)
            ->when($parent, fn ($query) => $query->where('parent_id', $parent->id), fn ($query) => $query->whereNull('parent_id'))
            ->max('order_column') ?? 0;

        $task = $project->tasks()->create([
            'parent_id' => $parent?->id,
            'task_group_id' => $taskGroupId,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'medium',
            'description' => $validated['description'] ?? null,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'estimation' => $validated['estimation'] ?? null,
            'fixed_price' => $validated['fixed_price'] ?? null,
            'billable' => $request->boolean('billable', false),
            'order_column' => $maxOrder + 1,
        ]);

        if (! empty($validated['label_ids'])) {
            $task->labels()->sync($validated['label_ids']);
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-attachments', 'public');
                $task->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getClientMimeType(),
                ]);
            }
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', $parent ? 'Subtask berhasil ditambahkan.' : 'Task berhasil ditambahkan.');
    }

    public function update(Request $request, Project $project, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'task_group_id' => ['nullable', 'exists:task_groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'description' => ['nullable', 'string'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'estimation' => ['nullable', 'numeric', 'min:0'],
            'fixed_price' => ['nullable', 'numeric', 'min:0'],
            'billable' => ['nullable', 'boolean'],
            'completed' => ['nullable', 'boolean'],
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => ['exists:labels,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $completedAt = $task->completed_at;
        if ($request->has('completed')) {
            $completedAt = $request->boolean('completed') ? ($task->completed_at ?? now()) : null;
        }

        if ($completedAt !== null && $task->hasOpenSubtasks()) {
            throw ValidationException::withMessages([
                'completed' => 'Task tidak bisa diselesaikan selama masih ada subtask yang belum selesai.',
            ]);
        }

        $taskGroupId = $task->isSubtask()
            ? $task->task_group_id
            : ($validated['task_group_id'] ?? $task->task_group_id);

        $task->update([
            'task_group_id' => $taskGroupId,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? $task->priority,
            'description' => $validated['description'] ?? null,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'estimation' => $validated['estimation'] ?? null,
            'fixed_price' => $validated['fixed_price'] ?? null,
            'billable' => $request->has('billable') ? $request->boolean('billable') : $task->billable,
            'completed_at' => $completedAt,
        ]);

        if (! $task->isSubtask()) {
            $task->subtasks()->update(['task_group_id' => $taskGroupId]);
        }

        if ($request->boolean('sync_labels') || $request->has('label_ids')) {
            $task->labels()->sync($validated['label_ids'] ?? []);
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('task-attachments', 'public');
                $task->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getClientMimeType(),
                ]);
            }
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Task berhasil diperbarui.');
    }

    public function move(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
            'task_group_id' => ['required', 'exists:task_groups,id'],
            'order_column' => ['nullable', 'integer', 'min:0'],
            'task_ids' => ['nullable', 'array'],
            'task_ids.*' => ['integer'],
        ]);

        $task = $project->tasks()->findOrFail($validated['task_id']);
        $group = TaskGroup::findOrFail($validated['task_group_id']);

        if ($task->isSubtask()) {
            return response()->json([
                'success' => false,
                'message' => 'Subtask mengikuti group parent-nya dan tidak bisa dipindahkan sendiri.',
            ], 422);
        }

        $completedAt = $task->completed_at;
        if (strcasecmp($group->name, 'Done') === 0) {
            if ($task->hasOpenSubtasks()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task tidak bisa dipindahkan ke Done selama masih ada subtask yang belum selesai.',
                ], 422);
            }

            $completedAt = $completedAt ?? now();
        } else {
            $completedAt = null;
        }

        $task->update([
            'task_group_id' => $group->id,
            'order_column' => $validated['order_column'] ?? $task->order_column,
            'completed_at' => $completedAt,
        ]);

        $task->subtasks()->update(['task_group_id' => $group->id]);

        if (! empty($validated['task_ids'])) {
            foreach ($validated['task_ids'] as $idx => $id) {
                $project->tasks()->where('id', $id)->update(['order_column' => $idx + 1]);
            }
        }

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        $tasks = $task->subtasks()->with('attachments')->get()->push($task->load('attachments'));

        foreach ($tasks as $item) {
            foreach ($item->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        }

        $task->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Task berhasil dihapus.');
    }

    public function destroyAttachment(Project $project, Task $task, TaskAttachment $attachment): RedirectResponse
    {
        if ($attachment->task_id === $task->id) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Lampiran berhasil dihapus.');
    }
}
