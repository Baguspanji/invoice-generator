<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    public function store(Request $request, Project $project, Task $task): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'start_timer') {
            $existing = $task->timeLogs()
                ->where('user_id', auth()->id())
                ->whereNull('timer_stop')
                ->whereNotNull('timer_start')
                ->first();

            if (! $existing) {
                $task->timeLogs()->create([
                    'user_id' => auth()->id(),
                    'timer_start' => now(),
                    'minutes' => 0,
                ]);
            }

            return redirect()
                ->route('projects.show', $project)
                ->with('success', 'Timer dimulai.');
        }

        if ($action === 'stop_timer') {
            $running = $task->timeLogs()
                ->where('user_id', auth()->id())
                ->whereNull('timer_stop')
                ->whereNotNull('timer_start')
                ->latest()
                ->first();

            if ($running) {
                $running->stop();
            }

            return redirect()
                ->route('projects.show', $project)
                ->with('success', 'Timer dihentikan.');
        }

        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'min:1'],
        ]);

        $task->timeLogs()->create([
            'user_id' => auth()->id(),
            'minutes' => $validated['minutes'],
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', "Logged {$validated['minutes']} menit ke task.");
    }

    public function destroy(Project $project, Task $task, TimeLog $timeLog): RedirectResponse
    {
        $timeLog->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Time log berhasil dihapus.');
    }
}
