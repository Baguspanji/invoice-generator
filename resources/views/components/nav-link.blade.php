@props(['href', 'active' => false, 'label'])

<a href="{{ $href }}"
    class="{{ $active ? 'bg-slate-100 text-dark dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:text-dark dark:text-slate-400 dark:hover:text-white' }} rounded-lg px-3 py-2 text-sm font-medium transition">
    {{ $label }}
</a>
