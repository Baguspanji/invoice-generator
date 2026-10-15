@extends('layouts.app')

@section('title', 'Proyek Saya — IceSum')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Portal Pelanggan</p>
        <h1 class="mt-1 text-2xl font-bold text-dark dark:text-white">Proyek Saya</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Daftar proyek yang terhubung dengan akun Anda.</p>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <form method="GET" action="{{ route('portal.projects.index') }}">
            <input type="text" name="search" value="{{ request('search') }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-sm focus:border-primary focus:bg-white focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                placeholder="Cari nama proyek...">
        </form>
    </div>

    @if ($projects->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-12 text-center dark:border-slate-700">
            <p class="text-sm italic text-slate-400">Belum ada proyek untuk akun Anda.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
                @php
                    $allTasks = $project->all_tasks_count;
                    $doneTasks = $project->completed_tasks_count;
                    $percent = $allTasks > 0 ? round(($doneTasks / $allTasks) * 100) : 0;
                @endphp
                <a href="{{ route('portal.projects.show', $project) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow dark:border-slate-700 dark:bg-slate-900">
                    <h2 class="text-base font-bold text-dark dark:text-white">{{ $project->name }}</h2>
                    <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $project->description }}</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-primary" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ $doneTasks }}/{{ $allTasks }} task selesai ({{ $percent }}%)</p>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $projects->links() }}</div>
    @endif
@endsection
