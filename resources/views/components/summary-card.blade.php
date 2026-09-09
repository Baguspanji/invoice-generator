@props(['label', 'value'])

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <div class="mb-2 flex items-start justify-between">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
        {{ $badge ?? '' }}
    </div>
    <div class="text-3xl font-extrabold tracking-tight text-dark dark:text-white">{{ $value }}</div>
</div>
