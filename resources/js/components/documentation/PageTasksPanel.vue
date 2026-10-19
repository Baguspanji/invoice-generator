<script setup>
import { computed, ref } from 'vue';
import { priorityBadgeClass } from './utils';

const props = defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
    projectTasks: {
        type: Array,
        default: () => [],
    },
    busy: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['link', 'unlink']);

const selected = ref('');

const availableTasks = computed(() => {
    const linkedIds = new Set(props.tasks.map((task) => Number(task.id)));

    return props.projectTasks.filter((task) => !linkedIds.has(Number(task.id)));
});

function link() {
    if (!selected.value) {
        return;
    }

    emit('link', Number(selected.value));
    selected.value = '';
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-sm font-semibold text-dark dark:text-white">Task Terkait</h3>

        <ul v-if="tasks.length" class="mt-3 space-y-2">
            <li v-for="task in tasks" :key="task.id"
                class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-2.5 py-2 text-sm dark:border-slate-800">
                <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-200" :title="task.title">
                    {{ task.title }}
                </span>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="priorityBadgeClass(task.priority)">
                    {{ task.priority?.label }}
                </span>
                <button type="button" :disabled="busy" class="shrink-0 text-slate-400 hover:text-red-500 disabled:opacity-50"
                    @click="$emit('unlink', task.id)">
                    &times;
                </button>
            </li>
        </ul>

        <p v-else class="mt-3 text-sm text-slate-400 dark:text-slate-500">Belum ada task terkait.</p>

        <div class="mt-3 flex gap-2">
            <select v-model="selected"
                class="min-w-0 flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <option value="">Pilih task…</option>
                <option v-for="task in availableTasks" :key="task.id" :value="task.id">{{ task.title }}</option>
            </select>
            <button type="button" :disabled="busy || !selected"
                class="rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                @click="link">
                Tautkan
            </button>
        </div>
    </div>
</template>
