<div class="relative" id="user-menu">
    <button type="button" onclick="toggleUserMenu(event)" aria-haspopup="true" aria-expanded="false" id="user-menu-button"
        class="flex items-center gap-2 py-1.5 pl-1.5 pr-2 transition ">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </span>
        <span
            class="hidden text-sm font-medium text-slate-700 sm:block dark:text-slate-200">{{ auth()->user()->name }}</span>
        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div id="user-menu-dropdown"
        class="absolute right-0 z-40 mt-2 hidden w-60 origin-top-right rounded-xl border border-slate-200 bg-white py-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-4 pb-3 pt-1 dark:border-slate-700">
            <p class="truncate text-sm font-semibold text-dark dark:text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
        </div>

        <a href="{{ route('invoices.create') }}"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-dark dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Buat Invoice Baru
        </a>
        <a href="{{ route('customers.create') }}"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-dark dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            Tambah Pelanggan
        </a>
        <a href="{{ route('settings.edit') }}"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-dark dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Pengaturan
        </a>

        <div class="my-2 border-t border-slate-100 dark:border-slate-700"></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-medium text-red-600 transition hover:bg-red-50 dark:hover:bg-red-950">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
                Keluar
            </button>
        </form>
    </div>
</div>

<script>
    function toggleUserMenu(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('user-menu-dropdown');
        const button = document.getElementById('user-menu-button');
        const isHidden = dropdown.classList.toggle('hidden');
        button.setAttribute('aria-expanded', String(!isHidden));
    }

    document.addEventListener('click', function(event) {
        const menu = document.getElementById('user-menu');
        const dropdown = document.getElementById('user-menu-dropdown');
        if (menu && dropdown && !menu.contains(event.target)) {
            dropdown.classList.add('hidden');
            document.getElementById('user-menu-button')?.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.getElementById('user-menu-dropdown')?.classList.add('hidden');
            document.getElementById('user-menu-button')?.setAttribute('aria-expanded', 'false');
        }
    });
</script>
