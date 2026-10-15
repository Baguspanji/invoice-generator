<script setup>
defineProps({
    filters: {
        type: Object,
        required: true,
    },
    hasActiveFilters: {
        type: Boolean,
        default: false,
    },
    users: {
        type: Array,
        default: () => [],
    },
    labels: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['reset']);
</script>

<template>
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <!-- Text Search -->
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" v-model="filters.search" placeholder="Cari judul atau deskripsi task..."
                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-primary focus:bg-white focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:bg-slate-900" />
            </div>

            <!-- Filters Group -->
            <div class="flex flex-wrap items-center gap-2">
                <select v-model="filters.priority"
                    class="rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-6 text-xs text-slate-700 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <option value="">Semua Prioritas</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>

                <select v-model="filters.user_id"
                    class="rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-6 text-xs text-slate-700 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <option value="">Semua Assignee</option>
                    <option value="unassigned">Tanpa Assignee</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>

                <select v-model="filters.label_id"
                    class="rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-6 text-xs text-slate-700 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <option value="">Semua Label</option>
                    <option v-for="lbl in labels" :key="lbl.id" :value="lbl.id">{{ lbl.name }}</option>
                </select>

                <!-- <select
                    v-model="filters.billing"
                    class="rounded-lg border border-slate-200 bg-white py-1.5 px-2.5 text-xs text-slate-700 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <option value="">Semua Billing</option>
                    <option value="billable">Billable</option>
                    <option value="invoiced">Invoiced</option>
                    <option value="non_billable">Non-billable</option>
                </select> -->

                <button v-if="hasActiveFilters" type="button" @click="$emit('reset')"
                    class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Reset
                </button>
            </div>
        </div>
    </div>
</template>
