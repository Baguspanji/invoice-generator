<script setup>
import { formatRupiah } from './utils';

defineProps({
    project: {
        type: Object,
        required: true,
    },
    viewMode: {
        type: String,
        default: 'board',
    },
    unbilledTasksCount: {
        type: Number,
        default: 0,
    },
    urls: {
        type: Object,
        required: true,
    },
});

defineEmits(['update:viewMode', 'openCreateModal', 'openInvoiceModal']);
</script>

<template>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a :href="urls.projectsIndex"
                    class="text-sm font-medium text-slate-500 hover:text-primary dark:text-slate-400">
                    &larr; Kembali ke Proyek
                </a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">{{ project.customer_name
                }}</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold text-dark dark:text-white">{{ project.name }}</h1>
            <!-- <p class="text-sm text-slate-500 dark:text-slate-400">
                Rate: <strong class="text-slate-700 dark:text-slate-300">{{ formatRupiah(project.rate) }}{{
                    project.pricing_type === 'hourly' ? '/jam' : '' }}</strong>
                &bull; Model: <span class="capitalize">{{ project.pricing_type }}</span>
            </p> -->
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- View Mode Switcher -->
            <div
                class="flex items-center rounded-lg border border-slate-200 bg-white p-1 shadow-xs dark:border-slate-700 dark:bg-slate-800">
                <button type="button" @click="$emit('update:viewMode', 'board')"
                    class="flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition"
                    :class="viewMode === 'board' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2">
                        </path>
                    </svg>
                    Board
                </button>
                <button type="button" @click="$emit('update:viewMode', 'list')"
                    class="flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-semibold transition"
                    :class="viewMode === 'list' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    List
                </button>
            </div>

            <button type="button" @click="$emit('openCreateModal')"
                class="flex items-center rounded-lg bg-primary px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tambah Task
            </button>

            <!-- <button
                v-if="unbilledTasksCount > 0"
                type="button"
                @click="$emit('openInvoiceModal')"
                class="flex items-center rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Terbitkan Invoice ({{ unbilledTasksCount }} Task)
            </button> -->

            <a v-if="urls.documentation" :href="urls.documentation"
                class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                Dokumentasi
            </a>

            <a :href="urls.projectEdit"
                class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                Pengaturan
            </a>
        </div>
    </div>
</template>
