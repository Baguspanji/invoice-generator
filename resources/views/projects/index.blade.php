@extends('layouts.app')

@section('title', 'Daftar Proyek — IceSum')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-dark dark:text-white">Manajemen Proyek</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Kelola proyek, pelacakan task, dan billing terintegrasi</p>
        </div>
        <a href="{{ route('projects.create') }}"
            class="flex items-center self-start rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-blue-700 sm:self-auto">
            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Proyek Baru
        </a>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <form method="GET" action="{{ route('projects.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-3 text-sm focus:border-primary focus:bg-white focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    placeholder="Cari nama proyek...">
            </div>

            <div class="w-full sm:w-64">
                <select name="customer_id" onchange="this.form.submit()"
                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:border-primary focus:bg-white focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="">Semua Pelanggan</option>
                    @foreach ($customers as $cust)
                        <option value="{{ $cust->id }}" @selected(request('customer_id') == $cust->id)>{{ $cust->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($projects->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-12 text-center dark:border-slate-700">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="mt-4 text-base font-semibold text-slate-800 dark:text-slate-200">Belum ada proyek</h3>
            <p class="mt-1 text-sm text-slate-500">Mulai dengan membuat proyek baru dan menghubungkannya dengan task & tagihan.</p>
            <div class="mt-6">
                <a href="{{ route('projects.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    + Buat Proyek Pertama
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
                @php
                    $allTasks = $project->all_tasks_count;
                    $doneTasks = $project->completed_tasks_count;
                    $percent = $allTasks > 0 ? round(($doneTasks / $allTasks) * 100) : 0;
                @endphp
                <div class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-slate-700 dark:bg-slate-900">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-primary">
                                    {{ $project->customer->name }}
                                </span>
                                <h3 class="mt-1 text-lg font-bold text-dark dark:text-white">
                                    <a href="{{ route('projects.show', $project) }}" class="hover:underline">
                                        {{ $project->name }}
                                    </a>
                                </h3>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $project->pricing_type === 'hourly' ? 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' }}">
                                {{ ucfirst($project->pricing_type) }}
                            </span>
                        </div>

                        <p class="mt-2 line-clamp-2 text-sm text-slate-500 dark:text-slate-400">
                            {{ $project->description ?: 'Tidak ada deskripsi.' }}
                        </p>

                        <div class="mt-4 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Rate: Rp {{ number_format((float) $project->rate, 0, ',', '.') }}{{ $project->pricing_type === 'hourly' ? '/jam' : '' }}</span>
                            <span>{{ $doneTasks }}/{{ $allTasks }} Task Selesai</span>
                        </div>

                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full bg-primary transition-all duration-300" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 dark:border-slate-800">
                        <div class="flex -space-x-2 overflow-hidden">
                            @forelse ($project->users->take(4) as $member)
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 text-xs font-medium text-slate-700 ring-2 ring-white dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-900" title="{{ $member->name }}">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </span>
                            @empty
                                <span class="text-xs text-slate-400">Belum ada tim</span>
                            @endforelse
                        </div>

                        <a href="{{ route('projects.show', $project) }}" class="text-sm font-semibold text-primary hover:underline">
                            Buka Board &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $projects->links() }}
        </div>
    @endif
@endsection
