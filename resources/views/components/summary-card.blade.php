@props(['label', 'value'])

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-2 flex items-start justify-between">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        {{ $badge ?? '' }}
    </div>
    <div class="text-3xl font-extrabold tracking-tight text-dark">{{ $value }}</div>
</div>
