<script setup>
import { formatRupiah, totalLoggedMinutes } from './utils';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    unbilledTasks: {
        type: Array,
        default: () => [],
    },
    cfg: {
        type: Object,
        required: true,
    },
});

defineEmits(['close']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold text-dark dark:text-white">Terbitkan Invoice dari Proyek</h3>
                <button type="button" @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                    &times;
                </button>
            </div>

            <form method="POST" :action="cfg.urls.generateInvoice" class="mt-4 space-y-4">
                <input type="hidden" name="_token" :value="cfg.csrf" />

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Pilih Task yang Akan Ditagihkan:</label>
                    <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 rounded-lg border border-slate-200 p-2 dark:divide-slate-800 dark:border-slate-700">
                        <label
                            v-for="ubTask in unbilledTasks"
                            :key="ubTask.id"
                            class="flex items-start gap-2.5 py-2 px-1 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer">
                            <input
                                type="checkbox"
                                name="task_ids[]"
                                :value="ubTask.id"
                                checked
                                class="mt-1 rounded text-primary focus:ring-primary"
                            />
                            <div class="text-sm">
                                <p class="font-medium text-slate-800 dark:text-slate-200">{{ ubTask.title }}</p>
                                <p class="text-xs text-slate-500">
                                    <span v-if="ubTask.fixed_price">Fixed: {{ formatRupiah(ubTask.fixed_price) }}</span>
                                    <span v-else>Logged: {{ totalLoggedMinutes(ubTask) }} menit</span>
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Jatuh Tempo Pembayaran</label>
                    <input
                        type="date"
                        name="due_date"
                        :value="cfg.defaultDueDate"
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    />
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Terbitkan Invoice Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
