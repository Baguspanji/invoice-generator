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
    filteredTasks: {
        type: Function,
        required: true,
    },
    cfg: {
        type: Object,
        required: true,
    },
    hasActiveFilters: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['editTask']);
</script>

<template>
    <div class="space-y-6">
        <div v-for="group in taskGroups" :key="group.id"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div
                class="flex items-center justify-between border-b border-slate-200 bg-slate-50/75 px-5 py-3 dark:border-slate-700 dark:bg-slate-800/60">
                <div class="flex items-center gap-2.5">
                    <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: group.color }"></span>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">{{
                        group.name }}</h2>
                    <span
                        class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                        {{ filteredTasks(group).length }}
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead
                        class="bg-slate-50/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:bg-slate-800/30">
                        <tr>
                            <th class="w-8 px-2 py-2.5 text-center"></th>
                            <th class="w-12 px-3 py-2.5 text-center">Status</th>
                            <th class="px-4 py-2.5 text-left">Task & Label</th>
                            <th class="px-4 py-2.5 text-left">Prioritas</th>
                            <th class="px-4 py-2.5 text-left">Ditugaskan</th>
                            <th class="px-4 py-2.5 text-left">Lampiran</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="list-tasks-container divide-y divide-slate-100 text-sm dark:divide-slate-800"
                        :data-group-id="group.id">
                        <tr v-for="task in filteredTasks(group)" :key="task.id" :data-task-id="task.id"
                            class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                            <!-- Drag Handle -->
                            <td class="w-8 px-2 py-3 text-center">
                                <div class="drag-handle inline-flex p-1 text-slate-300 hover:text-slate-600 dark:text-slate-600 dark:hover:text-slate-300"
                                    :class="hasActiveFilters ? 'cursor-not-allowed opacity-30' : 'cursor-grab active:cursor-grabbing'"
                                    :title="hasActiveFilters ? 'Reset filter untuk drag' : 'Geser untuk memindahkan'">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 8h16M4 16h16"></path>
                                    </svg>
                                </div>
                            </td>

                            <!-- Status Toggle -->
                            <td class="w-12 px-3 py-3 text-center">
                                <form method="POST" :action="`${cfg.urls.tasksBase}/${task.id}`">
                                    <input type="hidden" name="_token" :value="cfg.csrf" />
                                    <input type="hidden" name="_method" value="PUT" />
                                    <input type="hidden" name="title" :value="task.title" />
                                    <input type="hidden" name="completed" :value="isTaskCompleted(task) ? '0' : '1'" />
                                    <button type="submit"
                                        class="flex items-center justify-center text-slate-400 hover:text-primary"
                                        :title="isTaskCompleted(task) ? 'Tandai belum selesai' : 'Tandai selesai'">
                                        <svg v-if="isTaskCompleted(task)" class="h-5 w-5 text-emerald-500"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        <span v-else
                                            class="h-4 w-4 rounded-full border-2 border-slate-300 transition hover:border-primary dark:border-slate-600"></span>
                                    </button>
                                </form>
                            </td>

                            <!-- Title & Labels -->
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                    <button type="button" @click="$emit('editTask', task)"
                                        class="text-left font-medium text-slate-900 hover:text-primary dark:text-white transition"
                                        :class="{ 'line-through !text-slate-400 dark:!text-slate-500': isTaskCompleted(task) }">
                                        {{ task.title }}
                                    </button>
                                    <span v-for="lbl in task.labels" :key="lbl.id"
                                        class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                        :style="{ backgroundColor: lbl.color + '20', color: lbl.color }">
                                        {{ lbl.name }}
                                    </span>
                                </div>
                                <p v-if="task.description" class="text-xs text-slate-400 line-clamp-1">{{
                                    task.description }}</p>
                            </td>

                            <!-- Priority -->
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-semibold"
                                    :class="priorityBadgeClass(task.priority)">
                                    {{ priorityLabel(task.priority) }}
                                </span>
                            </td>

                            <!-- Assignee -->
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                <span v-if="task.assigned_user" class="inline-flex items-center gap-1.5">
                                    <span
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] font-semibold dark:bg-slate-700">
                                        {{ task.assigned_user.name.charAt(0).toUpperCase() }}
                                    </span>
                                    {{ task.assigned_user.name }}
                                </span>
                                <span v-else class="text-slate-400 italic">-</span>
                            </td>

                            <!-- Attachments -->
                            <td class="px-4 py-3">
                                <div v-if="task.attachments && task.attachments.length" class="flex flex-col gap-1">
                                    <a v-for="att in task.attachments" :key="att.id" :href="att.url" target="_blank"
                                        download
                                        class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                                        :title="`${att.file_name} (${att.human_size || ''})`">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                                            </path>
                                        </svg>
                                        <span class="max-w-[100px] truncate">{{ att.file_name }}</span>
                                    </a>
                                </div>
                                <span v-else class="text-xs text-slate-400 italic">-</span>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="$emit('editTask', task)"
                                        class="text-slate-400 hover:text-primary" title="Detail & Edit Task">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>

                                    <!-- Move Group Form -->
                                    <form method="POST" :action="`${cfg.urls.tasksBase}/${task.id}`" class="inline">
                                        <input type="hidden" name="_token" :value="cfg.csrf" />
                                        <input type="hidden" name="_method" value="PUT" />
                                        <input type="hidden" name="title" :value="task.title" />
                                        <!-- <select
                                            name="task_group_id"
                                            onchange="this.form.submit()"
                                            class="rounded border border-slate-200 bg-white py-0.5 px-1.5 text-[11px] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            <option
                                                v-for="targetGroup in taskGroups"
                                                :key="targetGroup.id"
                                                :value="targetGroup.id"
                                                :selected="targetGroup.id === group.id">
                                                {{ targetGroup.name }}
                                            </option>
                                        </select> -->
                                    </form>

                                    <!-- Delete -->
                                    <form method="POST" :action="`${cfg.urls.tasksBase}/${task.id}`"
                                        onsubmit="return confirm('Hapus task ini?')" class="inline">
                                        <input type="hidden" name="_token" :value="cfg.csrf" />
                                        <input type="hidden" name="_method" value="DELETE" />
                                        <button type="submit" class="text-slate-400 hover:text-red-500"
                                            title="Hapus task">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredTasks(group).length === 0">
                            <td colspan="7" class="py-4 text-center text-xs text-slate-400 italic">
                                Tidak ada task dalam status ini
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
