@extends('layouts.app')

@section('title', 'Buat Invoice — InvoiceSummary')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-dark">Buat Invoice Baru</h1>
        <p class="text-sm text-slate-500">Form pembuatan invoice akan diimplementasikan pada modul Auto-Invoice Engine.</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">Placeholder halaman pembuatan invoice.</p>
        <a href="{{ route('invoices.index') }}" class="mt-4 inline-block text-sm font-medium text-primary hover:text-blue-700">
            &larr; Kembali ke daftar invoice
        </a>
    </div>
@endsection
