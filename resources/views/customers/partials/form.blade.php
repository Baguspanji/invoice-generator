<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
