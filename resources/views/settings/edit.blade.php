@extends('layouts.app')

@section('title', 'Pengaturan — IceSum')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-dark dark:text-white">Pengaturan</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Data ini digunakan pada PDF invoice (blok DARI & instruksi pembayaran).</p>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach ($fields as $group => $groupFields)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h3 class="mb-4 text-lg font-bold text-dark dark:text-white">{{ $groups[$group] }}</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($groupFields as $key => $label)
                        <div class="{{ isset($textareas[$key]) ? 'md:col-span-2' : '' }}">
                            <label for="setting-{{ $key }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</label>
                            @if (isset($textareas[$key]))
                                <textarea name="settings[{{ $key }}]" id="setting-{{ $key }}" rows="2"
                                    class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ old('settings.'.$key, $values[$key] ?? '') }}</textarea>
                            @else
                                <input type="text" name="settings[{{ $key }}]" id="setting-{{ $key }}" value="{{ old('settings.'.$key, $values[$key] ?? '') }}"
                                    class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex items-center justify-end">
            <button type="submit"
                class="rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Simpan Pengaturan
            </button>
        </div>
    </form>
@endsection
