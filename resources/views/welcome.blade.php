@extends('layouts.guest')

@section('title', 'IceSum — Invoice Otomatis & Rekap Pendapatan')
@section('container', 'max-w-5xl')

@section('hero')
    <div class="mb-10 text-center">
        <div class="mb-4 inline-flex items-center justify-center">
            <x-app-logo size="lg" />
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-dark sm:text-4xl dark:text-white">
            Invoice otomatis, rekap pendapatan instan
        </h1>
        <p class="mx-auto mt-3 max-w-2xl text-sm text-slate-500 sm:text-base dark:text-slate-400">
            Catat transaksi, terbitkan PDF invoice, dan pantau arus kas masuk dalam satu layar.
            Tanpa sistem role berlapis — fokus pada pencatatan yang cepat dan angka yang akurat.
        </p>

        <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('login') }}"
                class="flex w-full justify-center rounded-lg border border-transparent bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:w-auto">
                Masuk ke Sistem
            </a>
            <a href="#fitur"
                class="flex w-full justify-center rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                Lihat Fitur
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div id="fitur" class="scroll-mt-8">
        <h2 class="text-center text-sm font-semibold tracking-wide text-slate-400 uppercase dark:text-slate-500">
            Modul Utama
        </h2>

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-4 inline-flex rounded-lg bg-blue-50 p-2 dark:bg-blue-500/10">
                    <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-dark dark:text-white">Auto-Invoice Engine</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li class="flex items-start">
                        <svg class="mt-0.5 mr-2 h-4 w-4 shrink-0 text-success" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Penomoran otomatis berurutan <span class="font-mono text-xs">INV/2026/09/0001</span></span>
                    </li>
                    <li class="flex items-start">
                        <svg class="mt-0.5 mr-2 h-4 w-4 shrink-0 text-success" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Item per baris lengkap dengan diskon, PPN, dan kategori</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="mt-0.5 mr-2 h-4 w-4 shrink-0 text-success" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Alur status Unpaid → Paid atau Unpaid → Cancelled</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="mt-0.5 mr-2 h-4 w-4 shrink-0 text-success" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>PDF invoice siap unduh kapan saja</span>
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-4 inline-flex rounded-lg bg-emerald-50 p-2 dark:bg-emerald-500/10">
                    <svg class="h-6 w-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 13h4v8H3zm0-6h4v4H3zm6 8h4v6H9zm0-10h4v8H9zm6 2h4v6h-4zm0 2h4v2h-4z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-dark dark:text-white">Revenue Summary</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li class="flex items-start">
                        <svg class="mt-0.5 mr-2 h-4 w-4 shrink-0 text-success" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>KPI pendapatan, piutang, dan volume invoice per periode</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="mt-0.5 mr-2 h-4 w-4 shrink-0 text-success" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Grafik harian, bulanan, dan tahunan</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="mt-0.5 mr-2 h-4 w-4 shrink-0 text-success" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Breakdown pendapatan per kategori produk atau jasa</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="mt-0.5 mr-2 h-4 w-4 shrink-0 text-success" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span>Ekspor .xlsx, PDF, dan CSV untuk periode tertentu</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h2 class="text-center text-sm font-semibold tracking-wide text-slate-400 uppercase dark:text-slate-500">
            Yang Dibedakan
        </h2>

        <div class="mt-6 grid grid-cols-1 gap-6 text-center sm:grid-cols-3">
            <div>
                <p class="text-2xl font-extrabold tracking-tight text-primary">DECIMAL(15,2)</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Akurasi rupiah tanpa error floating point
                </p>
            </div>
            <div>
                <p class="text-2xl font-extrabold tracking-tight text-primary">INV/YYYY/MM/NNNN</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Nomor invoice unik, berurutan, dan mudah dirujuk
                </p>
            </div>
            <div>
                <p class="text-2xl font-extrabold tracking-tight text-primary">Tanpa RBAC</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Struktur sederhana tanpa lapisan akses berlapis
                </p>
            </div>
        </div>
    </div>

    <p class="mt-8 text-center text-xs text-slate-400">
        <a href="https://kodebagus.com" target="_blank" rel="noopener noreferrer"
            class="text-primary hover:text-blue-700">
            KodeBagus
        </a> 2026 All Rights Reserved.
    </p>
@endsection
