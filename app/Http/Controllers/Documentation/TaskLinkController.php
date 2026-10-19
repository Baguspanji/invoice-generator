<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskLinkController extends Controller
{
    public function store(Request $request, DocumentationPage $page): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        $task = Task::findOrFail($validated['task_id']);

        if ((int) $task->project_id !== (int) $page->space->project_id) {
            throw ValidationException::withMessages([
                'task_id' => 'Task harus berasal dari project yang sama dengan workspace dokumentasi.',
            ]);
        }

        $page->tasks()->syncWithoutDetaching([
            $task->id => ['created_by' => $request->user()->id],
        ]);

        return response()->json(['success' => true, 'tasks' => $this->tasks($page)]);
    }

    public function destroy(DocumentationPage $page, Task $task): JsonResponse
    {
        $page->tasks()->detach($task->id);

        return response()->json(['success' => true, 'tasks' => $this->tasks($page)]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tasks(DocumentationPage $page): array
    {
        return $page->tasks()
            ->get()
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => [
                    'value' => $task->priority->value,
                    'label' => $task->priority->label(),
                    'badge_class' => $task->priority->badgeClass(),
                ],
            ])
            ->values()
            ->all();
    }
}
