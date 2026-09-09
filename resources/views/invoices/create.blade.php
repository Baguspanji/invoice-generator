@extends('layouts.app')

@section('title', 'Buat Invoice — IceSum')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-dark dark:text-white">Buat Invoice Baru</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Nomor invoice dibuat otomatis dengan format
            INV/TAHUN/BULAN/URUT.</p>
    </div>

    <form method="POST" action="{{ route('invoices.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="mb-4 text-lg font-bold text-dark dark:text-white">Data Invoice</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label for="customer_id"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300">Pelanggan</label>
                    <select name="customer_id" id="customer_id" required
                        class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="">— Pilih pelanggan —</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="invoice_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal
                        Terbit</label>
                    <input type="date" name="invoice_date" id="invoice_date"
                        value="{{ old('invoice_date', date('Y-m-d')) }}" required
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @error('invoice_date')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Jatuh
                        Tempo</label>
                    <input type="date" name="due_date" id="due_date"
                        value="{{ old('due_date', date('Y-m-d', strtotime('+14 days'))) }}" required
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    @error('due_date')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-dark dark:text-white">Daftar Item</h3>
                <button type="button" onclick="addItemRow()"
                    class="flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Item
                </button>
            </div>

            @error('items')
                <p class="mb-3 text-xs text-red-600">{{ $message }}</p>
            @enderror

            <div id="item-rows" class="space-y-3"></div>

            <div class="mt-4 border-t border-slate-200 pt-4 text-right dark:border-slate-700">
                <p class="text-sm text-slate-500 dark:text-slate-400">Estimasi Total: <span id="grand-total"
                        class="text-lg font-extrabold text-dark dark:text-white">Rp 0</span></p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('invoices.index') }}"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                Batal
            </a>
            <button type="submit"
                class="rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Simpan Invoice
            </button>
        </div>
    </form>

    <template id="item-row-template">
        <div
            class="item-row grid grid-cols-2 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-12 dark:border-slate-700 dark:bg-slate-800">
            <div class="col-span-2 md:col-span-3">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Nama Item</label>
                <input type="text" name="items[__INDEX__][item_name]" required placeholder="Nama produk/jasa"
                    class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            </div>
            <div class="col-span-1 md:col-span-2">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Kategori</label>
                <select name="items[__INDEX__][category]"
                    class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    <option value="">—</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-1 md:col-span-1">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Qty</label>
                <input type="number" name="items[__INDEX__][quantity]" value="1" min="1" required
                    oninput="updateGrandTotal()"
                    class="item-qty mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            </div>
            <div class="col-span-1 md:col-span-2">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Harga Satuan</label>
                <input type="number" name="items[__INDEX__][unit_price]" value="0" min="0" step="0.01"
                    required oninput="updateGrandTotal()"
                    class="item-price mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            </div>
            <div class="col-span-1 md:col-span-2">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">Diskon (Rp)</label>
                <input type="number" name="items[__INDEX__][discount_amount]" value="0" min="0" step="0.01"
                    oninput="updateGrandTotal()"
                    class="item-discount mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            </div>
            <div class="col-span-1 md:col-span-1">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">PPN (Rp)</label>
                <input type="number" name="items[__INDEX__][tax_amount]" value="0" min="0" step="0.01"
                    oninput="updateGrandTotal()"
                    class="item-tax mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            </div>
            <div class="col-span-2 flex items-end justify-end md:col-span-1">
                <button type="button" onclick="this.closest('.item-row').remove(); updateGrandTotal();"
                    class="rounded-lg border border-red-200 p-2 text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950"
                    title="Hapus item">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        let itemIndex = 0;

        function addItemRow() {
            const template = document.getElementById('item-row-template').innerHTML;
            const html = template.replaceAll('__INDEX__', itemIndex++);
            document.getElementById('item-rows').insertAdjacentHTML('beforeend', html);
            updateGrandTotal();
        }

        function updateGrandTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(function(row) {
                const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
                const price = parseFloat(row.querySelector('.item-price')?.value || 0);
                const discount = parseFloat(row.querySelector('.item-discount')?.value || 0);
                const tax = parseFloat(row.querySelector('.item-tax')?.value || 0);
                total += qty * price - discount + tax;
            });
            document.getElementById('grand-total').textContent =
                'Rp ' + total.toLocaleString('id-ID', {
                    maximumFractionDigits: 0
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            addItemRow();
        });
    </script>
@endpush
