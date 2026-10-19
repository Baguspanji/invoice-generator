@extends('layouts.app')

@section('title', 'Dokumentasi ' . $project->name . ' — IceSum')

@section('content')
    <div id="documentation-app" data-config='@json($config)' data-title="{{ $project->name }}">
        {{-- Skeleton before Vue mounts --}}
        <div class="animate-pulse space-y-4">
            <div class="h-8 w-64 rounded bg-slate-200 dark:bg-slate-800"></div>
            <div class="h-4 w-96 rounded bg-slate-200 dark:bg-slate-800"></div>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div class="h-96 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                <div class="h-96 rounded-xl bg-slate-200 lg:col-span-3 dark:bg-slate-800"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/documentation-workspace.js'])
@endpush
