<script setup>
import { activityDotClass } from './utils';

defineProps({
    activities: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-sm font-semibold text-dark dark:text-white">Riwayat Aktivitas</h3>

        <p v-if="!activities.length" class="mt-3 text-sm text-slate-400 dark:text-slate-500">
            Belum ada aktivitas.
        </p>

        <ul v-else class="mt-3 max-h-80 space-y-3 overflow-y-auto">
            <li v-for="activity in activities" :key="activity.id" class="flex gap-2">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="activityDotClass(activity.action)"></span>
                <div class="min-w-0">
                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ activity.description }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        {{ activity.user_name || 'Sistem' }} &middot; {{ activity.created_at_label }}
                    </p>
                </div>
            </li>
        </ul>
    </div>
</template>
