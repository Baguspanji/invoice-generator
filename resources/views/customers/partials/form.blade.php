<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tipe Pelanggan</label>
        <select name="type" id="type" required
            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            @foreach (\App\Enums\CustomerType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('type', ($customer->type ?? null) instanceof \BackedEnum ? $customer->type->value : ($customer->type ?? 'INDIVIDUAL')) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @error('type')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="identity_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300">No. Identitas (KTP/NPWP)</label>
        <input type="text" name="identity_number" id="identity_number" value="{{ old('identity_number', $customer->identity_number ?? '') }}"
            class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            placeholder="Nomor KTP untuk perseorangan">
        @error('identity_number')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nama</label>
        <input type="text" name="name" id="name" value="{{ old('name', $customer->name ?? '') }}" required
            class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            placeholder="Nama pelanggan / perusahaan">
        @error('name')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $customer->email ?? '') }}"
            class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            placeholder="email@perusahaan.com">
        @error('email')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Telepon</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone ?? '') }}"
            class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            placeholder="08xxxxxxxxxx">
        @error('phone')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Alamat</label>
        <textarea name="address" id="address" rows="3"
            class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            placeholder="Alamat lengkap">{{ old('address', $customer->address ?? '') }}</textarea>
        @error('address')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
        <input type="checkbox" name="create_account" value="1" @checked(old('create_account')) class="rounded text-primary focus:ring-primary">
        Buatkan akun login portal untuk pelanggan ini
    </label>
    <p class="mt-1 text-xs text-slate-500">Akun memakai role customer dan hanya bisa melihat proyek + invoice miliknya.</p>
    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label for="username" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username') }}"
                class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                placeholder="username-unik">
            @error('username')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="account_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email Akun (opsional)</label>
            <input type="email" name="account_email" id="account_email" value="{{ old('account_email') }}"
                class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                placeholder="Kosongkan = pakai email pelanggan">
            @error('account_email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="account_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Password (min 8)</label>
            <input type="password" name="account_password" id="account_password"
                class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                placeholder="********">
            @error('account_password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    @if (! empty($customer->users) && $customer->users->isNotEmpty())
        <div class="mt-3 text-xs text-slate-500">
            Akun terhubung: {{ $customer->users->map(fn ($u) => ($u->username ?? '-') . ' / ' . $u->email)->join(', ') }}
        </div>
    @endif
</div>
