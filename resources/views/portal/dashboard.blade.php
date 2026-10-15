@extends('layouts.app')

@section('title', 'Portal Pelanggan — IceSum')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Portal Pelanggan</p>
        <h1 class="mt-1 text-2xl font-bold text-dark dark:text-white">Halo, {{ $customer->name ?? auth()->user()->name }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Pantau progres proyek dan tagihan Anda di sini.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a href="{{ route('portal.projects.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Proyek Saya</p>
            <p class="mt-1 text-3xl font-bold text-dark dark:text-white">{{ $projects->count() }}</p>
            <p class="mt-2 text-sm font-medium text-primary">Lihat proyek &rarr;</p>
        </a>
        <a href="{{ route('portal.invoices.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow dark:border-slate-700 dark:bg-slate-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Invoice Saya</p>
            <p class="mt-1 text-3xl font-bold text-dark dark:text-white">{{ $invoices->count() }}</p>
            <p class="mt-2 text-sm font-medium text-primary">Lihat invoice &rarr;</p>
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">Proyek Terbaru</h2>
            <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($projects as $project)
                    <a href="{{ route('portal.projects.show', $project) }}" class="block py-2.5 hover:text-primary">
                        <p class="text-sm font-semibold">{{ $project->name }}</p>
                        <p class="text-xs text-slate-500">{{ $project->completed_tasks_count }}/{{ $project->all_tasks_count }} task selesai</p>
                    </a>
                @empty
                    <p class="py-3 text-sm italic text-slate-400">Belum ada proyek.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">Invoice Terbaru</h2>
            <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($invoices as $invoice)
                    <a href="{{ route('portal.invoices.show', $invoice) }}" class="flex items-center justify-between py-2.5 hover:text-primary">
                        <span class="text-sm font-semibold">{{ $invoice->invoice_number }}</span>
                        <x-status-badge :status="$invoice->status" />
                    </a>
                @empty
                    <p class="py-3 text-sm italic text-slate-400">Belum ada invoice.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
