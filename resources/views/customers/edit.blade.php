@extends('layouts.app')

@section('title', 'Ubah Pelanggan — IceSum')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-dark dark:text-white">Ubah Pelanggan</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Perbarui data {{ $customer->name }}.</p>
    </div>

    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf
        @method('PUT')
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            @include('customers.partials.form', ['customer' => $customer])

            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('customers.show', $customer) }}"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                    Batal
                </a>
                <button type="submit"
                    class="rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
@endsection
