<script setup>
import {
    priorityLabel,
    priorityBadgeClass,
    isTaskCompleted,
} from './utils';

defineProps({
    taskGroups: {
        type: Array,
        required: true,
    },
    cfg: {
        type: Object,
        required: true,
    },
});

defineEmits(['editTask']);
</script>

<template>
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
        <div
            v-for="group in taskGroups"
            :key="group.id"
            class="flex flex-col rounded-xl border border-slate-200 bg-slate-100/60 p-4 dark:border-slate-800 dark:bg-slate-900/40"
            :data-group-id="group.id">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: group.color }"></span>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ group.name }}</h2>
                </div>
                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    {{ group.tasks.length }}
                </span>
            </div>

            <!-- Draggable Task Cards Container -->
            <div
                class="kanban-tasks-container flex flex-1 flex-col gap-3 min-h-[160px]"
                :data-group-id="group.id">
                <div
                    v-for="task in group.tasks"
                    :key="task.id"
                    :data-task-id="task.id"
                    class="rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <!-- Top Bar: Priority + Drag Grip -->
                    <div class="mb-2 flex items-center justify-between gap-1">
                        <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold" :class="priorityBadgeClass(task.priority)">
                            {{ priorityLabel(task.priority) }}
                        </span>

                        <div class="drag-handle inline-flex cursor-grab active:cursor-grabbing p-0.5 text-slate-300 hover:text-slate-600 dark:text-slate-600 dark:hover:text-slate-300" title="Geser kartu">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                        </div>
                    </div>

                    <!-- Title -->
                    <div
                        @click="$emit('editTask', task)"
                        class="cursor-pointer text-sm font-semibold text-slate-900 hover:text-primary dark:text-white transition"
                        :class="{ 'line-through !text-slate-400': isTaskCompleted(task) }">
                        {{ task.title }}
                    </div>

                    <p v-if="task.description" class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ task.description }}
                    </p>

                    <!-- Labels -->
                    <div v-if="task.labels && task.labels.length" class="mt-2 flex flex-wrap gap-1">
                        <span
                            v-for="lbl in task.labels"
                            :key="lbl.id"
                            class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :style="{ backgroundColor: lbl.color + '20', color: lbl.color }">
                            {{ lbl.name }}
                        </span>
                    </div>

                    <!-- Attachments Preview -->
                    <div v-if="task.attachments && task.attachments.length" class="mt-2 flex flex-wrap items-center gap-1">
                        <a
                            v-for="att in task.attachments"
                            :key="att.id"
                            :href="att.url"
                            target="_blank"
                            download
                            class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600 hover:text-primary dark:bg-slate-700 dark:text-slate-300"
                            :title="att.file_name">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            <span class="max-w-[80px] truncate">{{ att.file_name }}</span>
                        </a>
                    </div>

                    <!-- Card Footer -->
                    <div class="mt-3 flex items-center justify-between gap-1 border-t border-slate-100 pt-2.5 text-xs text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                        <div class="flex min-w-0 items-center gap-1.5">
                            <span v-if="task.assigned_user" class="inline-flex min-w-0 items-center gap-1.5">
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[10px] font-semibold dark:bg-slate-700">
                                    {{ task.assigned_user.name.charAt(0).toUpperCase() }}
                                </span>
                                <span class="truncate">{{ task.assigned_user.name }}</span>
                            </span>
                            <span v-else class="text-slate-400 italic">Unassigned</span>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                type="button"
                                @click="$emit('editTask', task)"
                                class="text-slate-400 hover:text-primary"
                                title="Detail & Edit Task">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>

                            <form method="POST" :action="`${cfg.urls.tasksBase}/${task.id}`" onsubmit="return confirm('Hapus task ini?')">
                                <input type="hidden" name="_token" :value="cfg.csrf" />
                                <input type="hidden" name="_method" value="DELETE" />
                                <button type="submit" class="text-slate-400 hover:text-red-500" title="Hapus task">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div v-if="group.tasks.length === 0" class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-800">
                    Kosong
                </div>
            </div>
        </div>
    </div>
</template>
