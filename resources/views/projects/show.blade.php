@extends('layouts.app')

@section('title', $project->name . ' — IceSum')

@section('content')
    @php
        $boardConfig = [
            'csrf' => csrf_token(),
            'authUserId' => auth()->id(),
            'viewMode' => $viewMode,
            'defaultDueDate' => now()->addDays(14)->toDateString(),
            'filters' => $filters ?? [],
            'hasFilters' => $hasFilters ?? false,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'customer_name' => $project->customer?->name,
                'rate' => (float) $project->rate,
                'pricing_type' => $project->pricing_type,
            ],
            'taskGroups' => $project->taskGroups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'color' => $group->color,
                    'order_column' => $group->order_column,
                    'tasks' => $group->tasks->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'project_id' => $task->project_id,
                            'task_group_id' => $task->task_group_id,
                            'assigned_to_user_id' => $task->assigned_to_user_id,
                            'invoice_id' => $task->invoice_id,
                            'title' => $task->title,
                            'description' => $task->description,
                            'estimation' => $task->estimation,
                            'fixed_price' => $task->fixed_price,
                            'billable' => (bool) $task->billable,
                            'priority' => [
                                'value' => $task->priority->value,
                                'label' => $task->priority->label(),
                                'badge_class' => $task->priority->badgeClass(),
                            ],
                            'order_column' => $task->order_column,
                            'completed_at' => $task->completed_at?->toISOString(),
                            'assigned_user' => $task->assignedUser ? [
                                'id' => $task->assignedUser->id,
                                'name' => $task->assignedUser->name,
                            ] : null,
                            'labels' => $task->labels->map(fn ($l) => [
                                'id' => $l->id,
                                'name' => $l->name,
                                'color' => $l->color,
                            ])->values(),
                            'attachments' => $task->attachments->map(fn ($att) => [
                                'id' => $att->id,
                                'file_name' => $att->file_name,
                                'file_size' => $att->file_size,
                                'url' => $att->url(),
                                'human_size' => $att->humanSize(),
                            ])->values(),
                            'time_logs' => $task->timeLogs->map(fn ($log) => [
                                'id' => $log->id,
                                'user_id' => $log->user_id,
                                'minutes' => $log->minutes,
                                'stopped_at' => $log->stopped_at?->toISOString(),
                            ])->values(),
                            'invoice' => $task->invoice ? [
                                'id' => $task->invoice->id,
                                'invoice_number' => $task->invoice->invoice_number,
                            ] : null,
                        ];
                    })->values(),
                ];
            })->values(),
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'labels' => $labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color])->values(),
            'unbilledTasks' => $unbilledTasks->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'fixed_price' => $t->fixed_price,
                'time_logs' => $t->timeLogs->map(fn ($log) => [
                    'minutes' => $log->minutes,
                    'stopped_at' => $log->stopped_at?->toISOString(),
                ])->values(),
            ])->values(),
            'urls' => [
                'projectsIndex' => route('projects.index'),
                'projectEdit' => route('projects.edit', $project),
                'tasksStore' => route('projects.tasks.store', $project),
                'tasksBase' => url("/projects/{$project->id}/tasks"),
                'moveTask' => route('projects.tasks.move', $project),
                'invoicesBase' => url('/invoices'),
                'generateInvoice' => route('projects.invoices.generate', $project),
            ],
        ];
    @endphp

    <div id="project-board-app"
        data-config='@json($boardConfig)'
        data-title="{{ $project->name }}">
        {{-- Skeleton before Vue mounts --}}
        <div class="animate-pulse space-y-4">
            <div class="h-8 w-64 bg-slate-200 rounded dark:bg-slate-800"></div>
            <div class="h-4 w-96 bg-slate-200 rounded dark:bg-slate-800"></div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="h-64 bg-slate-200 rounded-xl dark:bg-slate-800"></div>
                <div class="h-64 bg-slate-200 rounded-xl dark:bg-slate-800"></div>
                <div class="h-64 bg-slate-200 rounded-xl dark:bg-slate-800"></div>
                <div class="h-64 bg-slate-200 rounded-xl dark:bg-slate-800"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/project-board.js'])
@endpush
