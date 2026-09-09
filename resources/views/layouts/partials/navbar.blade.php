<nav class="sticky top-0 z-30 border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <x-app-logo />
                    <span class="ml-2 hidden text-lg font-bold text-dark sm:block">InvoiceSummary</span>
                </a>
                <div class="ml-8 hidden items-center gap-1 md:flex">
                    <a href="{{ route('dashboard') }}"
                        class="{{ request()->routeIs('dashboard') ? 'bg-slate-100 text-dark' : 'text-slate-500 hover:text-dark' }} rounded-lg px-3 py-2 text-sm font-medium transition">
                        Dashboard
                    </a>
                    <a href="{{ route('invoices.index') }}"
                        class="{{ request()->routeIs('invoices.*') ? 'bg-slate-100 text-dark' : 'text-slate-500 hover:text-dark' }} rounded-lg px-3 py-2 text-sm font-medium transition">
                        Invoice
                    </a>
                </div>
            </div>

            <div class="mx-4 hidden max-w-lg flex-1 md:block">
                <form action="{{ route('invoices.index') }}" method="GET" class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-3 text-sm focus:border-primary focus:bg-white focus:ring-primary"
                        placeholder="Cari invoice, pelanggan...">
                </form>
            </div>

            <div class="flex items-center">
                <a href="{{ route('invoices.create') }}"
                    class="flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-blue-700">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span class="hidden sm:inline">Buat Invoice Baru</span>
                    <span class="sm:hidden">Buat</span>
                </a>
            </div>
        </div>
    </div>
</nav>
