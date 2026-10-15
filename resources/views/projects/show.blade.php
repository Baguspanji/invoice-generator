@extends('layouts.app')

@section('title', $project->name . ' — IceSum')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.index') }}" class="text-sm font-medium text-slate-500 hover:text-primary dark:text-slate-400">
                    &larr; Kembali ke Proyek
                </a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">{{ $project->customer->name }}</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold text-dark dark:text-white">{{ $project->name }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Rate: <strong class="text-slate-700 dark:text-slate-300">Rp {{ number_format((float) $project->rate, 0, ',', '.') }}{{ $project->pricing_type === 'hourly' ? '/jam' : '' }}</strong>
                &bull; Model: <span class="capitalize">{{ $project->pricing_type }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Mode Switcher -->
            <div class="flex items-center rounded-lg border border-slate-200 bg-white p-1 shadow-xs dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('projects.show', [$project, 'view' => 'board']) }}"
                    class="flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $viewMode === 'board' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                    Board
                </a>
                <a href="{{ route('projects.show', [$project, 'view' => 'list']) }}"
                    class="flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $viewMode === 'list' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    List
                </a>
            </div>

            <button type="button" onclick="document.getElementById('modal-create-task').showModal()"
                class="flex items-center rounded-lg bg-primary px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Task
            </button>

            @if ($unbilledTasks->isNotEmpty())
                <button type="button" onclick="document.getElementById('modal-generate-invoice').showModal()"
                    class="flex items-center rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Terbitkan Invoice ({{ $unbilledTasks->count() }} Task)
                </button>
            @endif

            <a href="{{ route('projects.edit', $project) }}"
                class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                Pengaturan
            </a>
        </div>
    </div>

    @if ($viewMode === 'list')
        <!-- List View Mode -->
        <div class="space-y-6">
            @foreach ($project->taskGroups as $group)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <!-- Group Header -->
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/75 px-5 py-3 dark:border-slate-700 dark:bg-slate-800/60">
                        <div class="flex items-center gap-2.5">
                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $group->color }};"></span>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ $group->name }}</h2>
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                {{ $group->tasks->count() }}
                            </span>
                        </div>
                    </div>

                    <!-- Tasks Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                            <thead class="bg-slate-50/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:bg-slate-800/30">
                                <tr>
                                    <th class="w-12 px-4 py-2.5 text-center">Status</th>
                                    <th class="px-4 py-2.5 text-left">Task & Label</th>
                                    <th class="px-4 py-2.5 text-left">Prioritas</th>
                                    <th class="px-4 py-2.5 text-left">Ditugaskan</th>
                                    <th class="px-4 py-2.5 text-left">Biaya / Estimasi</th>
                                    <th class="px-4 py-2.5 text-left">Waktu Log</th>
                                    <th class="px-4 py-2.5 text-left">Lampiran</th>
                                    <th class="px-4 py-2.5 text-left">Billing</th>
                                    <th class="px-4 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                                @forelse ($group->tasks as $task)
                                    @php
                                        $totalMins = $task->totalLoggedMinutes();
                                        $activeTimer = $task->timeLogs->first(fn ($log) => $log->isRunning() && $log->user_id === auth()->id());
                                    @endphp
                                    <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                        <!-- Complete Toggle -->
                                        <td class="px-4 py-3 text-center">
                                            <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="title" value="{{ $task->title }}">
                                                <input type="hidden" name="completed" value="{{ $task->isCompleted() ? '0' : '1' }}">
                                                <button type="submit" class="flex items-center justify-center text-slate-400 hover:text-primary" title="{{ $task->isCompleted() ? 'Tandai belum selesai' : 'Tandai selesai' }}">
                                                    @if ($task->isCompleted())
                                                        <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                    @else
                                                        <span class="h-4 w-4 rounded-full border-2 border-slate-300 transition hover:border-primary dark:border-slate-600"></span>
                                                    @endif
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Title, Desc & Labels -->
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                                <button type="button" onclick='openEditTaskModal(@json($task))'
                                                    class="text-left font-medium text-slate-900 hover:text-primary dark:text-white transition {{ $task->isCompleted() ? 'line-through !text-slate-400 dark:!text-slate-500' : '' }}">
                                                    {{ $task->title }}
                                                </button>
                                                @foreach ($task->labels as $label)
                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                          style="background-color: {{ $label->color }}20; color: {{ $label->color }};">
                                                        {{ $label->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            @if ($task->description)
                                                <p class="text-xs text-slate-400 line-clamp-1">{{ $task->description }}</p>
                                            @endif
                                        </td>

                                        <!-- Priority -->
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded px-2 py-0.5 text-xs font-semibold {{ $task->priority->badgeClass() }}">
                                                {{ $task->priority->label() }}
                                            </span>
                                        </td>

                                        <!-- Assignee -->
                                        <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                            @if ($task->assignedUser)
                                                <span class="inline-flex items-center gap-1.5">
                                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] font-semibold dark:bg-slate-700">
                                                        {{ strtoupper(substr($task->assignedUser->name, 0, 1)) }}
                                                    </span>
                                                    {{ $task->assignedUser->name }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 italic">-</span>
                                            @endif
                                        </td>

                                        <!-- Cost / Est -->
                                        <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                            @if ($task->fixed_price)
                                                <span class="font-semibold text-slate-900 dark:text-white">Rp {{ number_format((float) $task->fixed_price, 0, ',', '.') }}</span>
                                            @elseif ($task->estimation)
                                                <span>{{ $task->estimation }} jam est.</span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>

                                        <!-- Logged Time & Timer -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $totalMins }} mnt</span>
                                                @if ($activeTimer)
                                                    <form method="POST" action="{{ route('projects.tasks.time-log.store', [$project, $task]) }}">
                                                        @csrf
                                                        <input type="hidden" name="action" value="stop_timer">
                                                        <button type="submit" class="animate-pulse rounded bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-600 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400">
                                                            &#9632; Stop
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('projects.tasks.time-log.store', [$project, $task]) }}">
                                                        @csrf
                                                        <input type="hidden" name="action" value="start_timer">
                                                        <button type="submit" class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300" title="Mulai timer">
                                                            &#9654;
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Attachments -->
                                        <td class="px-4 py-3">
                                            @if ($task->attachments->isNotEmpty())
                                                <div class="flex flex-col gap-1">
                                                    @foreach ($task->attachments as $att)
                                                        <a href="{{ $att->url() }}" target="_blank" download
                                                            class="inline-flex items-center gap-1 text-xs text-primary hover:underline" title="{{ $att->file_name }} ({{ $att->humanSize() }})">
                                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                            <span class="max-w-[100px] truncate">{{ $att->file_name }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400 italic">-</span>
                                            @endif
                                        </td>

                                        <!-- Billing status -->
                                        <td class="px-4 py-3">
                                            @if ($task->invoice_id)
                                                <a href="{{ route('invoices.show', $task->invoice_id) }}"
                                                    class="inline-flex rounded bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 hover:underline">
                                                    #{{ $task->invoice->invoice_number }}
                                                </a>
                                            @elseif ($task->billable)
                                                <span class="inline-flex rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                                    Billable
                                                </span>
                                            @else
                                                <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                    Non-billable
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <!-- Edit Detail Button -->
                                                <button type="button" onclick='openEditTaskModal(@json($task))'
                                                    class="text-slate-400 hover:text-primary" title="Detail & Edit Task">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </button>

                                                <!-- Move Group Form -->
                                                <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="title" value="{{ $task->title }}">
                                                    <select name="task_group_id" onchange="this.form.submit()"
                                                        class="rounded border border-slate-200 bg-white py-0.5 px-1.5 text-[11px] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                        @foreach ($project->taskGroups as $targetGroup)
                                                            <option value="{{ $targetGroup->id }}" @selected($targetGroup->id === $group->id)>{{ $targetGroup->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>

                                                <!-- Delete -->
                                                <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" onsubmit="return confirm('Hapus task ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-slate-400 hover:text-red-500" title="Hapus task">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-4 text-center text-xs text-slate-400 italic">
                                            Tidak ada task dalam status ini
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Kanban Board Mode -->
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
            @foreach ($project->taskGroups as $group)
                <div class="flex flex-col rounded-xl border border-slate-200 bg-slate-100/60 p-4 dark:border-slate-800 dark:bg-slate-900/40"
                     data-group-id="{{ $group->id }}">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $group->color }};"></span>
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ $group->name }}</h2>
                        </div>
                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                            {{ $group->tasks->count() }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col gap-3 min-h-[150px]">
                        @forelse ($group->tasks as $task)
                            @php
                                $totalMins = $task->totalLoggedMinutes();
                                $activeTimer = $task->timeLogs->first(fn ($log) => $log->isRunning() && $log->user_id === auth()->id());
                            @endphp
                            <div class="rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <!-- Top Bar: Priority & Badges -->
                                <div class="mb-2 flex items-center justify-between gap-1">
                                    <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $task->priority->badgeClass() }}">
                                        {{ $task->priority->label() }}
                                    </span>

                                    <div class="flex items-center gap-1">
                                        @if ($task->invoice_id)
                                            <a href="{{ route('invoices.show', $task->invoice_id) }}" title="Lihat Invoice"
                                                class="shrink-0 rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-semibold text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                                INV #{{ $task->invoice->invoice_number }}
                                            </a>
                                        @elseif ($task->billable)
                                            <span class="shrink-0 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                                Billable
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="cursor-pointer text-sm font-semibold text-slate-900 hover:text-primary dark:text-white transition"
                                     onclick='openEditTaskModal(@json($task))'>
                                    {{ $task->title }}
                                </div>

                                @if ($task->description)
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $task->description }}
                                    </p>
                                @endif

                                <!-- Labels -->
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

                                <!-- Attachments Preview -->
                                @if ($task->attachments->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap items-center gap-1">
                                        @foreach ($task->attachments as $att)
                                            <a href="{{ $att->url() }}" target="_blank" download
                                                class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600 hover:text-primary dark:bg-slate-700 dark:text-slate-300"
                                                title="{{ $att->file_name }}">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                <span class="max-w-[80px] truncate">{{ $att->file_name }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Card Footer -->
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-1 border-t border-slate-100 pt-2.5 text-xs text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                                    <div class="flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>{{ $totalMins }} mnt</span>
                                        @if ($task->fixed_price)
                                            <span class="font-medium text-slate-700 dark:text-slate-300">(Rp {{ number_format((float) $task->fixed_price, 0, ',', '.') }})</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <button type="button" onclick='openEditTaskModal(@json($task))'
                                            class="text-slate-400 hover:text-primary" title="Detail & Edit Task">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>

                                        @if ($activeTimer)
                                            <form method="POST" action="{{ route('projects.tasks.time-log.store', [$project, $task]) }}">
                                                @csrf
                                                <input type="hidden" name="action" value="stop_timer">
                                                <button type="submit" class="animate-pulse rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-600 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400">
                                                    &#9632; Stop
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('projects.tasks.time-log.store', [$project, $task]) }}">
                                                @csrf
                                                <input type="hidden" name="action" value="start_timer">
                                                <button type="submit" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300">
                                                    &#9654; Timer
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" onsubmit="return confirm('Hapus task ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-red-500" title="Hapus task">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
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
    @endif

    <!-- Modal Buat Task -->
    <dialog id="modal-create-task" class="backdrop:bg-slate-900/40 rounded-xl p-0 shadow-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 w-full max-w-xl">
        <div class="p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold text-dark dark:text-white">Tambah Task Baru</h3>
                <button type="button" onclick="document.getElementById('modal-create-task').close()" class="text-slate-400 hover:text-slate-600">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('projects.tasks.store', $project) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kolom Status</label>
                        <select name="task_group_id" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            @foreach ($project->taskGroups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Prioritas</label>
                        <select name="priority" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            @foreach (\App\Enums\TaskPriority::cases() as $p)
                                <option value="{{ $p->value }}" @selected($p->value === 'medium')>{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Judul Task</label>
                    <input type="text" name="title" required placeholder="Nama pekerjaan..."
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi</label>
                    <textarea name="description" rows="2" placeholder="Catatan detail..."
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"></textarea>
                </div>

                <!-- Labels Checkboxes -->
                @if (isset($labels) && $labels->isNotEmpty())
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Label / Tags</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($labels as $label)
                                <label class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1 text-xs hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 cursor-pointer">
                                    <input type="checkbox" name="label_ids[]" value="{{ $label->id }}" class="rounded text-primary focus:ring-primary">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $label->color }};"></span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ $label->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Estimasi (Jam)</label>
                        <input type="number" step="0.5" min="0" name="estimation" placeholder="Misal: 4"
                            class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    </div>

                    @if ($project->pricing_type === 'fixed')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Fixed Price (Rp)</label>
                            <input type="number" min="0" name="fixed_price" placeholder="Harga task"
                                class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Ditugaskan ke</label>
                            <select name="assigned_to_user_id" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <option value="">Belum Ditugaskan</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <!-- Attachments File Upload -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Lampiran Berkas (Multiple)</label>
                    <input type="file" name="attachments[]" multiple
                        class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="billable" value="1" id="create-billable" checked class="rounded text-primary focus:ring-primary">
                    <label for="create-billable" class="text-sm font-medium text-slate-700 dark:text-slate-300">Dapat ditagihkan ke invoice (Billable)</label>
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <button type="button" onclick="document.getElementById('modal-create-task').close()"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        Batal
                    </button>
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Simpan Task
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Modal Edit & Detail Task -->
    <dialog id="modal-edit-task" class="backdrop:bg-slate-900/40 rounded-xl p-0 shadow-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 w-full max-w-xl">
        <div class="p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold text-dark dark:text-white">Detail & Edit Task</h3>
                <button type="button" onclick="document.getElementById('modal-edit-task').close()" class="text-slate-400 hover:text-slate-600">
                    &times;
                </button>
            </div>

            <form id="form-edit-task" method="POST" action="" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="sync_labels" value="1">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kolom Status</label>
                        <select id="edit-task-group-id" name="task_group_id" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            @foreach ($project->taskGroups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Prioritas</label>
                        <select id="edit-priority" name="priority" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            @foreach (\App\Enums\TaskPriority::cases() as $p)
                                <option value="{{ $p->value }}">{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Judul Task</label>
                    <input type="text" id="edit-title" name="title" required
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi</label>
                    <textarea id="edit-description" name="description" rows="3" placeholder="Catatan detail..."
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"></textarea>
                </div>

                <!-- Labels Checkboxes -->
                @if (isset($labels) && $labels->isNotEmpty())
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Label / Tags</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($labels as $label)
                                <label class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1 text-xs hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 cursor-pointer">
                                    <input type="checkbox" name="label_ids[]" value="{{ $label->id }}" class="edit-label-checkbox rounded text-primary focus:ring-primary">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $label->color }};"></span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ $label->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Estimasi (Jam)</label>
                        <input type="number" step="0.5" min="0" id="edit-estimation" name="estimation"
                            class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    </div>

                    @if ($project->pricing_type === 'fixed')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Fixed Price (Rp)</label>
                            <input type="number" min="0" id="edit-fixed-price" name="fixed_price"
                                class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Ditugaskan ke</label>
                            <select id="edit-assigned-to" name="assigned_to_user_id" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <option value="">Belum Ditugaskan</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <!-- Existing Attachments List -->
                <div id="edit-attachments-wrapper" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Lampiran Tersimpan</label>
                    <div id="edit-attachments-list" class="flex flex-col gap-1.5 max-h-36 overflow-y-auto"></div>
                </div>

                <!-- Upload New Attachments -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tambah Lampiran Baru</label>
                    <input type="file" name="attachments[]" multiple
                        class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="billable" value="1" id="edit-billable" class="rounded text-primary focus:ring-primary">
                    <label for="edit-billable" class="text-sm font-medium text-slate-700 dark:text-slate-300">Dapat ditagihkan ke invoice (Billable)</label>
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <button type="button" onclick="document.getElementById('modal-edit-task').close()"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        Batal
                    </button>
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Hidden Attachment Delete Form -->
    <form id="form-delete-attachment" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- Modal Generate Invoice -->
    @if ($unbilledTasks->isNotEmpty())
        <dialog id="modal-generate-invoice" class="backdrop:bg-slate-900/40 rounded-xl p-0 shadow-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 w-full max-w-lg">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-dark dark:text-white">Terbitkan Invoice dari Proyek</h3>
                    <button type="button" onclick="document.getElementById('modal-generate-invoice').close()" class="text-slate-400 hover:text-slate-600">
                        &times;
                    </button>
                </div>

                <form method="POST" action="{{ route('projects.invoices.generate', $project) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Pilih Task yang Akan Ditagihkan:</label>
                        <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 rounded-lg border border-slate-200 p-2 dark:divide-slate-800 dark:border-slate-700">
                            @foreach ($unbilledTasks as $ubTask)
                                <label class="flex items-start gap-2.5 py-2 px-1 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer">
                                    <input type="checkbox" name="task_ids[]" value="{{ $ubTask->id }}" checked
                                        class="mt-1 rounded text-primary focus:ring-primary">
                                    <div class="text-sm">
                                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $ubTask->title }}</p>
                                        <p class="text-xs text-slate-500">
                                            @if ($ubTask->fixed_price)
                                                Fixed: Rp {{ number_format((float) $ubTask->fixed_price, 0, ',', '.') }}
                                            @else
                                                Logged: {{ $ubTask->totalLoggedMinutes() }} menit (Estimasi: Rp {{ number_format(ceil($ubTask->totalLoggedMinutes() / 60) * (float) $project->rate, 0, ',', '.') }})
                                            @endif
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Jatuh Tempo Pembayaran</label>
                        <input type="date" name="due_date" value="{{ now()->addDays(14)->toDateString() }}"
                            class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    </div>

                    <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button type="button" onclick="document.getElementById('modal-generate-invoice').close()"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                            Batal
                        </button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Terbitkan Invoice Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif
@endsection

@push('scripts')
<script>
    function openEditTaskModal(task) {
        const modal = document.getElementById('modal-edit-task');
        const form = document.getElementById('form-edit-task');

        form.action = `/projects/{{ $project->id }}/tasks/${task.id}`;

        document.getElementById('edit-task-group-id').value = task.task_group_id;
        
        let priorityVal = 'medium';
        if (typeof task.priority === 'object' && task.priority !== null) {
            priorityVal = task.priority.value || 'medium';
        } else if (typeof task.priority === 'string') {
            priorityVal = task.priority;
        }
        document.getElementById('edit-priority').value = priorityVal;
        
        document.getElementById('edit-title').value = task.title || '';
        document.getElementById('edit-description').value = task.description || '';

        const assignedEl = document.getElementById('edit-assigned-to');
        if (assignedEl) {
            assignedEl.value = task.assigned_to_user_id || '';
        }

        const estEl = document.getElementById('edit-estimation');
        if (estEl) {
            estEl.value = task.estimation || '';
        }

        const fpEl = document.getElementById('edit-fixed-price');
        if (fpEl) {
            fpEl.value = task.fixed_price ? Math.round(Number(task.fixed_price)) : '';
        }

        document.getElementById('edit-billable').checked = Boolean(task.billable);

        // Labels
        const taskLabelIds = (task.labels || []).map(l => Number(l.id));
        document.querySelectorAll('.edit-label-checkbox').forEach(cb => {
            cb.checked = taskLabelIds.includes(Number(cb.value));
        });

        // Attachments
        const attachmentsContainer = document.getElementById('edit-attachments-list');
        attachmentsContainer.innerHTML = '';

        if (task.attachments && task.attachments.length > 0) {
            document.getElementById('edit-attachments-wrapper').classList.remove('hidden');
            task.attachments.forEach(att => {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between py-1.5 px-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs';

                const linkWrapper = document.createElement('div');
                linkWrapper.className = 'flex items-center gap-1.5 truncate mr-2';
                linkWrapper.innerHTML = `
                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    <a href="${att.url}" target="_blank" download class="font-medium text-primary hover:underline truncate">${att.file_name}</a>
                    <span class="text-slate-400 shrink-0">(${att.human_size || ''})</span>
                `;

                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'text-slate-400 hover:text-red-500 shrink-0 p-1 transition';
                delBtn.title = 'Hapus lampiran';
                delBtn.innerHTML = `<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;
                delBtn.onclick = () => {
                    if (confirm(`Hapus lampiran ${att.file_name}?`)) {
                        const delForm = document.getElementById('form-delete-attachment');
                        delForm.action = `/projects/{{ $project->id }}/tasks/${task.id}/attachments/${att.id}`;
                        delForm.submit();
                    }
                };

                row.appendChild(linkWrapper);
                row.appendChild(delBtn);
                attachmentsContainer.appendChild(row);
            });
        } else {
            document.getElementById('edit-attachments-wrapper').classList.add('hidden');
        }

        modal.showModal();
    }
</script>
@endpush
