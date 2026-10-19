<script setup>
defineProps({
    versions: {
        type: Array,
        default: () => [],
    },
    busy: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['restore']);
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-sm font-semibold text-dark dark:text-white">Riwayat Versi</h3>

        <p v-if="!versions.length" class="mt-3 text-sm text-slate-400 dark:text-slate-500">
            Belum ada snapshot versi.
        </p>

        <ul v-else class="mt-3 space-y-2">
            <li v-for="version in versions" :key="version.id"
                class="rounded-lg border border-slate-100 p-2.5 text-sm dark:border-slate-800">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-slate-700 dark:text-slate-200">
                        #{{ version.version_number }} &middot; {{ version.title }}
                    </span>
                    <button type="button" :disabled="busy"
                        class="rounded-md border border-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        @click="$emit('restore', version.id)">
                        Pulihkan
                    </button>
                </div>
                <p v-if="version.change_summary" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ version.change_summary }}
                </p>
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                    {{ version.creator_name || 'Sistem' }} &middot; {{ version.created_at_label }}
                </p>
            </li>
        </ul>
    </div>
</template>
