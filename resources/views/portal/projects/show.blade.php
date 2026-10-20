@extends('layouts.app')

@section('title', $project->name . ' — Portal — IceSum')

@section('content')
    <div class="mb-6">
        <a href="{{ route('portal.projects.index') }}" class="text-sm font-medium text-slate-500 hover:text-primary dark:text-slate-400">
            &larr; Kembali ke Proyek Saya
        </a>
        <h1 class="mt-1 text-2xl font-bold text-dark dark:text-white">{{ $project->name }}</h1>
        @if ($project->description)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $project->description }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
        @foreach ($project->taskGroups as $group)
            <div class="flex flex-col rounded-xl border border-slate-200 bg-slate-100/60 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full" style="background-color: {{ $group->color }};"></span>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ $group->name }}</h2>
                    </div>
                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        {{ $group->tasks->count() }}
                    </span>
                </div>

                <div class="flex flex-1 flex-col gap-3">
                    @forelse ($group->tasks as $task)
                        <div class="rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs dark:border-slate-700 dark:bg-slate-800">
                            <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $task->priority->badgeClass() }}">
                                {{ $task->priority->label() }}
                            </span>
                            <p class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">{{ $task->title }}</p>
                            @if ($task->description)
                                <p class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $task->description }}</p>
                            @endif
                            @if ($task->labels->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($task->labels as $label)
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            style="background-color: {{ $label->color }}20; color: {{ $label->color }};">
                                            {{ $label->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            <p class="mt-2 border-t border-slate-100 pt-2 text-xs text-slate-500 dark:border-slate-700/60">
                                {{ $task->assignedUser->name ?? 'Unassigned' }}
                            </p>
                            @if ($task->subtasks->isNotEmpty())
                                @php($subtaskDone = $task->subtasks->whereNotNull('completed_at')->count())
                                <details class="group mt-2 border-t border-slate-100 pt-2 dark:border-slate-700/60">
                                    <summary class="flex cursor-pointer list-none items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">
                                        <svg class="h-3 w-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        {{ $subtaskDone }}/{{ $task->subtasks->count() }} subtask
                                    </summary>
                                    <ul class="mt-2 space-y-1.5 border-l-2 border-slate-100 pl-2.5 dark:border-slate-700">
                                        @foreach ($task->subtasks as $subtask)
                                            <li class="text-xs">
                                                <span class="{{ $subtask->completed_at ? 'text-slate-400 line-through' : 'text-slate-700 dark:text-slate-300' }}">
                                                    {{ $subtask->title }}
                                                </span>
                                                <span class="ml-1 text-slate-400">· {{ $subtask->assignedUser->name ?? 'Unassigned' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-800">
                            Kosong
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@endsection
