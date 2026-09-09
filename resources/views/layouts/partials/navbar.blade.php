<nav class="sticky top-0 z-30 border-b border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 print:hidden">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-2">
            <div class="flex min-w-0 items-center">
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center">
                    <x-app-logo />
                    <span class="ml-2 hidden text-lg font-bold text-dark sm:block dark:text-white">IceSum</span>
                </a>
                <div class="ml-6 hidden items-center gap-1 md:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" label="Dashboard" />
                    <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')" label="Invoice" />
                    <x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')" label="Pelanggan" />
                    <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" label="Laporan" />
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <x-theme-toggle />
                <x-user-dropdown />
            </div>
        </div>
    </div>
</nav>
