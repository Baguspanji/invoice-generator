<script setup>
import { reactive, ref, watch } from 'vue';
import FileDropzone from './FileDropzone.vue';
import AttachmentPreviewModal from './AttachmentPreviewModal.vue';
import { isPreviewableImage } from './utils';

const preview = ref(null);

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    task: {
        type: Object,
        default: null,
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
});

defineEmits(['close']);

const form = reactive({
    id: null,
    task_group_id: '',
    priority: 'medium',
    title: '',
    description: '',
    estimation: '',
    fixed_price: '',
    assigned_to_user_id: '',
    label_ids: [],
    billable: false,
    attachments: [],
});

watch(() => props.task, (t) => {
    if (!t) return;
    form.id = t.id;
    form.task_group_id = t.task_group_id;
    form.priority = typeof t.priority === 'object' && t.priority !== null ? t.priority.value : (t.priority || 'medium');
    form.title = t.title || '';
    form.description = t.description || '';
    form.estimation = t.estimation || '';
    form.fixed_price = t.fixed_price ? Math.round(Number(t.fixed_price)) : '';
    form.assigned_to_user_id = t.assigned_to_user_id || '';
    form.billable = Boolean(t.billable);
    form.label_ids = (t.labels || []).map(l => l.id);
    form.attachments = t.attachments || [];
}, { immediate: true });
</script>

<template>
    <div v-if="show && task" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold text-dark dark:text-white">Detail & Edit Task</h3>
                <button type="button" @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">
                    &times;
                </button>
            </div>

            <form
                method="POST"
                :action="`${cfg.urls.tasksBase}/${form.id}`"
                enctype="multipart/form-data"
                class="mt-4 space-y-4">
                <input type="hidden" name="_token" :value="cfg.csrf" />
                <input type="hidden" name="_method" value="PUT" />
                <input type="hidden" name="sync_labels" value="1" />

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
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
                        class="mt-1 block w-full rounded-lg border border-slate-200 py-2 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Deskripsi</label>
                    <textarea
                        name="description"
                        v-model="form.description"
                        rows="3"
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
                                v-model="form.label_ids"
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

                <!-- Existing Attachments -->
                <div v-if="form.attachments && form.attachments.length">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Lampiran Tersimpan</label>
                    <div class="flex flex-col gap-1.5 max-h-36 overflow-y-auto">
                        <div
                            v-for="att in form.attachments"
                            :key="att.id"
                            class="flex items-center justify-between py-1.5 px-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
                            <div class="flex items-center gap-1.5 truncate mr-2">
                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                <button
                                    v-if="isPreviewableImage(att)"
                                    type="button"
                                    @click="preview = att"
                                    class="font-medium text-primary hover:underline truncate"
                                    :title="`${att.file_name} — klik untuk pratinjau`">{{ att.file_name }}</button>
                                <a v-else :href="att.url" target="_blank" download class="font-medium text-primary hover:underline truncate">{{ att.file_name }}</a>
                                <span class="text-slate-400 shrink-0">({{ att.human_size || '' }})</span>
                            </div>
                            <form method="POST" :action="`${cfg.urls.tasksBase}/${form.id}/attachments/${att.id}`" onsubmit="return confirm('Hapus lampiran ini?')">
                                <input type="hidden" name="_token" :value="cfg.csrf" />
                                <input type="hidden" name="_method" value="DELETE" />
                                <button type="submit" class="text-slate-400 hover:text-red-500 shrink-0 p-1 transition" title="Hapus lampiran">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Upload New Attachments Dropzone -->
                <FileDropzone label="Tambah Lampiran Baru" name="attachments[]" />

                <div class="flex items-center gap-2 pt-2">
                    <input type="hidden" name="billable" value="0" />
                    <input
                        type="checkbox"
                        name="billable"
                        value="1"
                        id="edit-billable-vue"
                        v-model="form.billable"
                        class="rounded text-primary focus:ring-primary"
                    />
                    <label for="edit-billable-vue" class="text-sm font-medium text-slate-700 dark:text-slate-300">
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
                        Simpan Perubahan
                    </button>
                </div>
            </form>

            <AttachmentPreviewModal :attachment="preview" @close="preview = null" />
        </div>
    </div>
</template>
