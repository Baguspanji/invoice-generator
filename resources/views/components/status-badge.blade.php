@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $classes = match ($value) {
        'PAID' => 'bg-emerald-50 text-success dark:bg-emerald-500/10 dark:text-emerald-400',
        'UNPAID' => 'bg-amber-50 text-warning dark:bg-amber-500/10 dark:text-amber-400',
        default => 'bg-slate-100 text-slate-500 dark:bg-slate-500/10 dark:text-slate-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5 $classes"]) }}>
    {{ $value }}
</span>
