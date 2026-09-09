<nav aria-label="Navigasi bawah"
    class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95 print:hidden md:hidden">
    <div class="grid grid-cols-5 items-end px-2 pb-[env(safe-area-inset-bottom)] pt-1">
        <a href="{{ route('dashboard') }}"
            class="{{ request()->routeIs('dashboard') ? 'text-primary' : 'text-slate-400 dark:text-slate-500' }} flex flex-col items-center gap-1 py-2 text-[11px] font-medium">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Dashboard
        </a>
        <a href="{{ route('invoices.index') }}"
            class="{{ request()->routeIs('invoices.*') && ! request()->routeIs('invoices.create') ? 'text-primary' : 'text-slate-400 dark:text-slate-500' }} flex flex-col items-center gap-1 py-2 text-[11px] font-medium">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Invoice
        </a>
        <a href="{{ route('invoices.create') }}" aria-label="Buat invoice"
            class="flex flex-col items-center gap-1 py-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">
            <span class="flex h-12 w-12 -translate-y-4 items-center justify-center rounded-full bg-primary text-white shadow-lg transition hover:bg-blue-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </span>
            <span class="-mt-3">Buat</span>
        </a>
        <a href="{{ route('customers.index') }}"
            class="{{ request()->routeIs('customers.*') ? 'text-primary' : 'text-slate-400 dark:text-slate-500' }} flex flex-col items-center gap-1 py-2 text-[11px] font-medium">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Pelanggan
        </a>
        <a href="{{ route('reports.index') }}"
            class="{{ request()->routeIs('reports.*') ? 'text-primary' : 'text-slate-400 dark:text-slate-500' }} flex flex-col items-center gap-1 py-2 text-[11px] font-medium">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Laporan
        </a>
    </div>
</nav>
