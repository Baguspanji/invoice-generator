<script setup>
import { reactive, watch } from 'vue';
import FileDropzone from './FileDropzone.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    taskGroups: {
        type: Array,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    labels: {
        type: Array,
        default: () => [],
    },
    project: {
        type: Object,
        required: true,
    },
    cfg: {
        type: Object,
        required: true,
    },
    parentTask: {
        type: Object,
        default: null,
    },
});

defineEmits(['close']);

const form = reactive({
    task_group_id: props.taskGroups[0]?.id || '',
    priority: 'medium',
    title: '',
    description: '',
    estimation: '',
    fixed_price: '',
    assigned_to_user_id: '',
    label_ids: [],
    billable: false,
});

watch(() => props.taskGroups, (newGroups) => {
    if (!form.task_group_id && newGroups?.length) {
        form.task_group_id = newGroups[0].id;
    }
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold text-dark dark:text-white">{{ parentTask ? 'Tambah Subtask' : 'Tambah Task Baru' }}</h3>
                <button type="button" @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                    &times;
                </button>
            </div>

            <form method="POST" :action="cfg.urls.tasksStore" enctype="multipart/form-data" class="mt-4 space-y-4">
                <input type="hidden" name="_token" :value="cfg.csrf" />
                <input v-if="parentTask" type="hidden" name="parent_id" :value="parentTask.id" />

                <p v-if="parentTask" class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    Parent: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ parentTask.title }}</span>
                </p>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div v-if="!parentTask">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Kolom Status</label>
                        <select
                            name="task_group_id"
                            v-model="form.task_group_id"
                            required
                            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <option v-for="g in taskGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Prioritas</label>
                        <select
                            name="priority"
                            v-model="form.priority"
                            required
                            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Judul Task</label>
                    <input
                        type="text"
                        name="title"
                        v-model="form.title"
                        required
                        placeholder="Nama pekerjaan..."
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi</label>
                    <textarea
                        name="description"
                        v-model="form.description"
                        rows="2"
                        placeholder="Catatan detail..."
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    ></textarea>
                </div>

                <!-- Labels -->
                <div v-if="labels.length">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Label / Tags</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="lbl in labels"
                            :key="lbl.id"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1 text-xs hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 cursor-pointer">
                            <input
                                type="checkbox"
                                name="label_ids[]"
                                :value="lbl.id"
                                class="rounded text-primary focus:ring-primary"
                            />
                            <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: lbl.color }"></span>
                            <span class="text-slate-700 dark:text-slate-300">{{ lbl.name }}</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Estimasi (Jam)</label>
                        <input
                            type="number"
                            step="0.5"
                            min="0"
                            name="estimation"
                            v-model="form.estimation"
                            placeholder="Misal: 4"
                            class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                    </div>

                    <div v-if="project.pricing_type === 'fixed'">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Fixed Price (Rp)</label>
                        <input
                            type="number"
                            min="0"
                            name="fixed_price"
                            v-model="form.fixed_price"
                            placeholder="Harga task"
                            class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                    </div>
                    <div v-else>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Ditugaskan ke</label>
                        <select
                            name="assigned_to_user_id"
                            v-model="form.assigned_to_user_id"
                            class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <option value="">Belum Ditugaskan</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Drag & drop attachments -->
                <FileDropzone label="Lampiran Berkas (Multiple)" name="attachments[]" />

                <div class="flex items-center gap-2 pt-2">
                    <input type="hidden" name="billable" value="0" />
                    <input
                        type="checkbox"
                        name="billable"
                        value="1"
                        id="create-billable-vue"
                        v-model="form.billable"
                        class="rounded text-primary focus:ring-primary"
                    />
                    <label for="create-billable-vue" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Dapat ditagihkan ke invoice (Billable)
                    </label>
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
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Simpan Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
