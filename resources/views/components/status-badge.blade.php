@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $classes = match ($value) {
        'PAID' => 'bg-emerald-50 text-success',
        'UNPAID' => 'bg-amber-50 text-warning',
        default => 'bg-slate-100 text-slate-500',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5 $classes"]) }}>
    {{ $value }}
</span>
