@extends('layouts.app')

@section('title', 'Ubah Proyek — IceSum')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-dark dark:text-white">Ubah Proyek</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Edit rincian proyek: {{ $project->name }}</p>
        </div>
        <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Hapus proyek ini beserta seluruh task-nya?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400">
                Hapus Proyek
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('projects.update', $project) }}">
        @csrf
        @method('PUT')
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Pelanggan / Klien</label>
                    <select name="customer_id" id="customer_id" required
                        class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        @foreach ($customers as $cust)
                            <option value="{{ $cust->id }}" @selected(old('customer_id', $project->customer_id) == $cust->id)>{{ $cust->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nama Proyek</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $project->name) }}" required
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pricing_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Model Penagihan</label>
                    <select name="pricing_type" id="pricing_type" required
                        class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="hourly" @selected(old('pricing_type', $project->pricing_type) === 'hourly')>Hourly (Per Jam dari Timer / Log)</option>
                        <option value="fixed" @selected(old('pricing_type', $project->pricing_type) === 'fixed')>Fixed Price (Harga Tetap Per Task)</option>
                    </select>
                    @error('pricing_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="rate" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Default Rate (Rp / Jam atau Base Rate)</label>
                    <input type="number" name="rate" id="rate" value="{{ old('rate', (int) $project->rate) }}" min="0" required
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @error('rate')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi / Scope of Work</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ old('description', $project->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($users->isNotEmpty())
                    @php $assignedIds = $project->users->pluck('id')->all(); @endphp
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Anggota Tim Terlibat</label>
                        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($users as $user)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-2.5 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 cursor-pointer">
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                        @checked(is_array(old('user_ids')) ? in_array($user->id, old('user_ids')) : in_array($user->id, $assignedIds))
                                        class="rounded text-primary focus:ring-primary">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $user->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                <a href="{{ route('projects.show', $project) }}"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                    Batal
                </a>
                <button type="submit"
                    class="rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Perbarui Proyek
                </button>
            </div>
        </div>
    </form>
@endsection
