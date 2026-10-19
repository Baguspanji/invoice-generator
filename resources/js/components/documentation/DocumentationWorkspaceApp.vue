<script setup>
import { computed, reactive, ref, watch } from 'vue';
import DocumentTree from './DocumentTree.vue';
import DocumentEditor from './DocumentEditor.vue';
import VersionHistoryPanel from './VersionHistoryPanel.vue';
import PageTasksPanel from './PageTasksPanel.vue';
import PageAttachmentsPanel from './PageAttachmentsPanel.vue';
import ActivityPanel from './ActivityPanel.vue';
import DocumentPreviewModal from './DocumentPreviewModal.vue';
import PageCommentsPanel from './PageCommentsPanel.vue';
import { statusBadgeClass, statusLabel } from './utils';

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
    title: {
        type: String,
        default: '',
    },
});

const cfg = props.config;

const tree = ref(cfg.tree || []);
const page = ref(cfg.page ? JSON.parse(JSON.stringify(cfg.page)) : null);
const pageTitle = ref(page.value?.title || '');
const content = ref(page.value?.content || null);

const versions = ref(page.value?.versions || []);
const tasks = ref(page.value?.tasks || []);
const attachments = ref(page.value?.attachments || []);
const activities = ref(page.value?.activities || []);
const comments = ref(page.value?.comments || []);

const saveState = ref('idle');
const busy = ref(false);
const conflict = ref(false);

const searchQuery = ref('');
const searchResults = ref([]);
const searchOpen = ref(false);

const filterCategory = ref('');
const filterStatus = ref('');

const preview = reactive({
    open: false,
    loading: false,
    html: '',
    title: '',
});

const createModal = reactive({
    open: false,
    parentId: null,
    title: '',
    categoryId: '',
});

const status = computed(() => page.value?.status || { value: 'draft', label: 'Draft' });

const filteredTree = computed(() => filterNodes(tree.value, filterCategory.value, filterStatus.value));

const exportMarkdownUrl = computed(() =>
    page.value ? `${cfg.pagesBase}/${page.value.id}/export/markdown` : '#'
);

const exportPdfUrl = computed(() => (page.value ? `${cfg.pagesBase}/${page.value.id}/export/pdf` : '#'));

const attachmentUploadUrl = computed(() =>
    page.value ? `${cfg.pagesBase}/${page.value.id}/attachments` : null
);

const saveLabel = computed(() => {
    switch (saveState.value) {
        case 'saving':
            return 'Menyimpan…';
        case 'saved':
            return 'Tersimpan';
        case 'error':
            return 'Gagal menyimpan';
        case 'conflict':
            return 'Konflik perubahan';
        default:
            return page.value?.updated_at_label ? `Diperbarui ${page.value.updated_at_label}` : 'Belum disimpan';
    }
});

let saveTimer = null;
let hydrating = true;

watch([pageTitle, content], () => {
    if (hydrating || !page.value) {
        return;
    }

    scheduleSave();
});

function scheduleSave() {
    saveState.value = 'saving';
    clearTimeout(saveTimer);
    saveTimer = setTimeout(save, 1200);
}

async function save() {
    if (!page.value) {
        return;
    }

    try {
        const response = await fetch(`${cfg.pagesBase}/${page.value.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: JSON.stringify({
                title: pageTitle.value,
                content: content.value,
                lock_version: page.value.lock_version,
            }),
        });

        if (response.status === 409) {
            const data = await response.json().catch(() => ({}));
            saveState.value = 'conflict';
            conflict.value = true;
            window.showToast?.(data.message || 'Konten telah diubah oleh pengguna lain.', 'error');

            return;
        }

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            saveState.value = 'error';
            window.showToast?.(data.message || 'Gagal menyimpan dokumentasi.', 'error');

            return;
        }

        const data = await response.json();
        page.value.lock_version = data.page.lock_version;
        page.value.status = data.page.status;
        page.value.updated_at_label = data.page.updated_at_label;
        saveState.value = 'saved';
    } catch (e) {
        saveState.value = 'error';
        window.showToast?.('Gagal menyimpan. Periksa koneksi Anda.', 'error');
    }
}

async function pageAction(path, payload = {}) {
    busy.value = true;

    try {
        const response = await fetch(path, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            window.showToast?.(data.message || 'Aksi gagal dijalankan.', 'error');

            return null;
        }

        return await response.json();
    } catch (e) {
        window.showToast?.('Aksi gagal dijalankan.', 'error');

        return null;
    } finally {
        busy.value = false;
    }
}

async function publish() {
    if (!page.value) {
        return;
    }

    clearTimeout(saveTimer);
    await save();

    const data = await pageAction(`${cfg.pagesBase}/${page.value.id}/publish`, {});

    if (data) {
        page.value.status = data.page.status;
        window.showToast?.('Halaman dipublikasikan.');
        window.location.reload();
    }
}

async function submitReview() {
    if (!page.value) {
        return;
    }

    clearTimeout(saveTimer);
    await save();

    const data = await pageAction(`${cfg.pagesBase}/${page.value.id}/submit-review`);

    if (data) {
        page.value.status = data.page.status;
        refreshActivities();
        window.showToast?.('Halaman dikirim untuk review.');
    }
}

async function restore(versionId) {
    if (!page.value) {
        return;
    }

    const data = await pageAction(`${cfg.pagesBase}/${page.value.id}/restore/${versionId}`);

    if (data) {
        window.showToast?.('Versi dipulihkan.');
        window.location.reload();
    }
}

async function linkTask(taskId) {
    if (!page.value) {
        return;
    }

    const data = await pageAction(`${cfg.pagesBase}/${page.value.id}/tasks`, { task_id: taskId });

    if (data) {
        tasks.value = data.tasks;
        refreshActivities();
        window.showToast?.('Task ditautkan.');
    }
}

async function unlinkTask(taskId) {
    if (!page.value) {
        return;
    }

    busy.value = true;

    try {
        const response = await fetch(`${cfg.pagesBase}/${page.value.id}/tasks/${taskId}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
        });

        if (response.ok) {
            const data = await response.json();
            tasks.value = data.tasks;
            refreshActivities();
            window.showToast?.('Tautan task dilepas.');
        }
    } finally {
        busy.value = false;
    }
}

async function uploadAttachment(file) {
    if (!page.value) {
        return;
    }

    busy.value = true;

    try {
        const body = new FormData();
        body.append('file', file);

        const response = await fetch(`${cfg.pagesBase}/${page.value.id}/attachments`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
            body,
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            window.showToast?.(data.message || 'Gagal mengunggah lampiran.', 'error');

            return;
        }

        const data = await response.json();
        attachments.value = [...attachments.value, data.attachment];
        refreshActivities();
        window.showToast?.('Lampiran diunggah.');
    } finally {
        busy.value = false;
    }
}

async function removeAttachment(attachmentId) {
    if (!page.value) {
        return;
    }

    busy.value = true;

    try {
        const response = await fetch(`${cfg.pagesBase}/${page.value.id}/attachments/${attachmentId}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
        });

        if (response.ok) {
            attachments.value = attachments.value.filter((item) => item.id !== attachmentId);
            refreshActivities();
            window.showToast?.('Lampiran dihapus.');
        }
    } finally {
        busy.value = false;
    }
}

function filterNodes(nodes, categoryId, statusValue) {
    const result = [];

    for (const node of nodes || []) {
        const children = filterNodes(node.children || [], categoryId, statusValue);
        const matchesCategory = !categoryId || String(node.category_id) === String(categoryId);
        const matchesStatus = !statusValue || node.status?.value === statusValue;

        if ((matchesCategory && matchesStatus) || children.length) {
            result.push({ ...node, children });
        }
    }

    return result;
}

async function refreshActivities() {
    if (!page.value) {
        return;
    }

    try {
        const response = await fetch(`${cfg.pagesBase}/${page.value.id}/activities`, {
            headers: { Accept: 'application/json' },
        });

        if (response.ok) {
            const data = await response.json();
            activities.value = data.activities;
        }
    } catch (e) {
        // abaikan kegagalan refresh log
    }
}

async function openPreview() {
    if (!page.value) {
        return;
    }

    preview.open = true;
    preview.loading = true;
    preview.html = '';
    preview.title = pageTitle.value;

    try {
        const response = await fetch(`${cfg.pagesBase}/${page.value.id}/preview`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: JSON.stringify({ content: content.value }),
        });

        if (response.ok) {
            const data = await response.json();
            preview.html = data.html;
            preview.title = data.title || pageTitle.value;
        } else {
            window.showToast?.('Gagal memuat pratinjau.', 'error');
        }
    } catch (e) {
        window.showToast?.('Gagal memuat pratinjau.', 'error');
    } finally {
        preview.loading = false;
    }
}

async function addComment(body) {
    if (!page.value) {
        return;
    }

    busy.value = true;

    try {
        const response = await fetch(`${cfg.pagesBase}/${page.value.id}/comments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: JSON.stringify({ body }),
        });

        if (response.ok) {
            const data = await response.json();
            comments.value = data.comments;
            window.showToast?.('Komentar ditambahkan.');
        } else if (response.status === 422) {
            const data = await response.json().catch(() => ({}));
            window.showToast?.(data.message || 'Komentar tidak valid.', 'error');
        } else {
            window.showToast?.('Gagal mengirim komentar.', 'error');
        }
    } catch (e) {
        window.showToast?.('Gagal mengirim komentar.', 'error');
    } finally {
        busy.value = false;
    }
}

async function removeComment(commentId) {
    if (!page.value) {
        return;
    }

    busy.value = true;

    try {
        const response = await fetch(`${cfg.pagesBase}/${page.value.id}/comments/${commentId}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
        });

        if (response.ok) {
            const data = await response.json();
            comments.value = data.comments;
            window.showToast?.('Komentar dihapus.');
        } else {
            window.showToast?.('Gagal menghapus komentar.', 'error');
        }
    } catch (e) {
        window.showToast?.('Gagal menghapus komentar.', 'error');
    } finally {
        busy.value = false;
    }
}

function openCreateModal(parentId = null) {
    createModal.open = true;
    createModal.parentId = parentId;
    createModal.title = '';
    createModal.categoryId = '';
}

function reload() {
    window.location.reload();
}

function submitCreate() {
    if (!createModal.title.trim()) {
        return;
    }

    postForm(cfg.pagesStore, {
        title: createModal.title,
        parent_id: createModal.parentId,
        category_id: createModal.categoryId,
    });
}

function deletePage(pageId) {
    if (!window.confirm('Hapus halaman ini beserta versi dan lampirannya?')) {
        return;
    }

    postForm(`${cfg.pagesBase}/${pageId}`, {}, 'DELETE');
}

function postForm(url, fields, method = 'POST') {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';

    appendField(form, '_token', cfg.csrf);

    if (method !== 'POST') {
        appendField(form, '_method', method);
    }

    Object.entries(fields).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        appendField(form, key, value);
    });

    document.body.appendChild(form);
    form.submit();
}

function appendField(form, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
}

let searchTimer = null;

function onSearchInput() {
    clearTimeout(searchTimer);
    searchOpen.value = searchQuery.value.trim().length > 0;
    searchTimer = setTimeout(runSearch, 300);
}

async function runSearch() {
    const term = searchQuery.value.trim();

    if (term === '' && !filterCategory.value && !filterStatus.value) {
        searchResults.value = [];

        return;
    }

    const params = new URLSearchParams({ project_id: cfg.project.id });

    if (term !== '') {
        params.set('q', term);
    }

    if (filterCategory.value) {
        params.set('category_id', filterCategory.value);
    }

    if (filterStatus.value) {
        params.set('status', filterStatus.value);
    }

    try {
        const response = await fetch(`${cfg.search}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (response.ok) {
            const data = await response.json();
            searchResults.value = data.results;
            searchOpen.value = true;
        }
    } catch (e) {
        searchResults.value = [];
    }
}

function applyFilters() {
    if (searchQuery.value.trim() !== '' || filterCategory.value || filterStatus.value) {
        runSearch();
    } else {
        searchResults.value = [];
        searchOpen.value = false;
    }
}

hydrating = false;
</script>

<template>
    <div>
        <div class="mb-4 flex items-center gap-2 text-sm">
            <a :href="cfg.project.url" class="font-medium text-slate-500 hover:text-primary dark:text-slate-400">
                &larr; {{ cfg.project.name }}
            </a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-xs font-semibold uppercase tracking-wider text-primary">Dokumentasi</span>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <aside class="lg:col-span-1">
                <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                    <div class="relative">
                        <input v-model="searchQuery" type="search" placeholder="Cari judul atau isi…"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            @input="onSearchInput" @focus="searchOpen = searchQuery.trim().length > 0" />

                        <div v-if="searchOpen && searchResults.length"
                            class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                            <a v-for="result in searchResults" :key="result.id" :href="result.url"
                                class="block border-b border-slate-100 px-3 py-2 last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800">
                                <div class="flex items-center justify-between gap-2">
                                    <span
                                        class="min-w-0 truncate text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ result.title }}
                                    </span>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                        :class="statusBadgeClass(result.status?.value)">
                                        {{ result.status?.label }}
                                    </span>
                                </div>
                                <p v-if="result.excerpt"
                                    class="mt-0.5 line-clamp-2 text-xs text-slate-400 dark:text-slate-500">
                                    {{ result.excerpt }}
                                </p>
                            </a>
                        </div>
                    </div>

                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <select v-model="filterCategory" @change="applyFilters"
                            class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <option value="">Semua kategori</option>
                            <option v-for="category in cfg.categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <select v-model="filterStatus" @change="applyFilters"
                            class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <option value="">Semua status</option>
                            <option v-for="item in cfg.statuses" :key="item.value" :value="item.value">
                                {{ item.label }}
                            </option>
                        </select>
                    </div>

                    <button type="button"
                        class="mt-3 flex w-full items-center justify-center rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="openCreateModal(null)">
                        <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Halaman Baru
                    </button>

                    <nav class="mt-3">
                        <DocumentTree :nodes="filteredTree" :active-id="page?.id ?? null" :base-url="cfg.workspaceUrl"
                            @create-child="openCreateModal" @delete="deletePage" />
                    </nav>
                </div>
            </aside>

            <section class="space-y-4 lg:col-span-3">
                <div v-if="!page"
                    class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                    Pilih halaman di samping atau buat halaman baru untuk mulai menulis dokumentasi.
                </div>

                <template v-else>
                    <div v-if="conflict"
                        class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                        <span>Halaman ini telah diubah di tempat lain. Muat ulang untuk melihat versi terbaru.</span>
                        <button type="button" class="rounded-md border border-amber-300 px-2 py-1 text-xs font-semibold"
                            @click="reload">
                            Muat Ulang
                        </button>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <input v-model="pageTitle" type="text"
                                class="min-w-0 flex-1 rounded-lg border border-transparent bg-transparent px-1 py-1 text-xl font-bold text-dark focus:border-slate-200 focus:outline-none dark:text-white dark:focus:border-slate-700" />

                            <span class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="statusBadgeClass(status.value)">
                                {{ statusLabel(status.value) }}
                            </span>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                            <span>{{ saveLabel }}</span>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="button" :disabled="busy"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                @click="submitReview">
                                Kirim Review
                            </button>
                            <button type="button" :disabled="busy"
                                class="rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                                @click="publish">
                                Publikasikan
                            </button>
                            <button type="button" :disabled="busy"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                @click="openPreview">
                                Pratinjau
                            </button>
                            <a :href="exportMarkdownUrl"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                Ekspor .md
                            </a>
                            <a :href="exportPdfUrl"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                Ekspor PDF
                            </a>
                        </div>
                    </div>

                    <DocumentEditor v-model="content" :upload-url="attachmentUploadUrl" :csrf="cfg.csrf" />

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <VersionHistoryPanel :versions="versions" :busy="busy" @restore="restore" />
                        <ActivityPanel :activities="activities" />
                        <PageTasksPanel :tasks="tasks" :project-tasks="cfg.projectTasks" :busy="busy" @link="linkTask"
                            @unlink="unlinkTask" />
                        <PageAttachmentsPanel :attachments="attachments" :busy="busy" @upload="uploadAttachment"
                            @remove="removeAttachment" />
                        <PageCommentsPanel :comments="comments" :busy="busy" @add="addComment"
                            @remove="removeComment" />
                    </div>
                </template>
            </section>
        </div>

        <div v-if="createModal.open"
            class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="createModal.open = false">
            <div class="w-full max-w-md rounded-xl bg-white p-5 dark:bg-slate-900">
                <h3 class="text-lg font-semibold text-dark dark:text-white">Halaman Baru</h3>

                <label class="mt-4 block text-sm font-medium text-slate-600 dark:text-slate-300">
                    Judul
                    <input v-model="createModal.title" type="text"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        @keyup.enter="submitCreate" />
                </label>

                <label v-if="cfg.categories.length"
                    class="mt-3 block text-sm font-medium text-slate-600 dark:text-slate-300">
                    Kategori
                    <select v-model="createModal.categoryId"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="">Tanpa kategori</option>
                        <option v-for="category in cfg.categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </label>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 dark:border-slate-700 dark:text-slate-300"
                        @click="createModal.open = false">
                        Batal
                    </button>
                    <button type="button"
                        class="rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="submitCreate">
                        Simpan
                    </button>
                </div>
            </div>
        </div>

        <DocumentPreviewModal :open="preview.open" :html="preview.html" :title="preview.title"
            :loading="preview.loading" @close="preview.open = false" />
    </div>
</template>
