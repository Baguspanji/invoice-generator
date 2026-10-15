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

class TaskController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'task_group_id' => ['required', 'exists:task_groups,id'],
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

        $maxOrder = $project->tasks()
            ->where('task_group_id', $validated['task_group_id'])
            ->max('order_column') ?? 0;

        $task = $project->tasks()->create([
            'task_group_id' => $validated['task_group_id'],
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'medium',
            'description' => $validated['description'] ?? null,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'estimation' => $validated['estimation'] ?? null,
            'fixed_price' => $validated['fixed_price'] ?? null,
            'billable' => $request->boolean('billable', true),
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
            ->with('success', 'Task berhasil ditambahkan.');
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

        $task->update([
            'task_group_id' => $validated['task_group_id'] ?? $task->task_group_id,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? $task->priority,
            'description' => $validated['description'] ?? null,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'estimation' => $validated['estimation'] ?? null,
            'fixed_price' => $validated['fixed_price'] ?? null,
            'billable' => $request->boolean('billable', true),
            'completed_at' => $completedAt,
        ]);

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
        ]);

        $task = $project->tasks()->findOrFail($validated['task_id']);
        $group = TaskGroup::findOrFail($validated['task_group_id']);

        $completedAt = $task->completed_at;
        if (strcasecmp($group->name, 'Done') === 0) {
            $completedAt = $completedAt ?? now();
        } else {
            $completedAt = null;
        }

        $task->update([
            'task_group_id' => $group->id,
            'order_column' => $validated['order_column'] ?? $task->order_column,
            'completed_at' => $completedAt,
        ]);

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        foreach ($task->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
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
